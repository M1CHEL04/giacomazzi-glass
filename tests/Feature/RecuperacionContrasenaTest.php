<?php
namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * El cambio de contraseña por código sólo tiene que andar con el código
 * verificado. Antes alcanzaba con pedirlo para el correo de cualquiera.
 */
class RecuperacionContrasenaTest extends TestCase
{
    use RefreshDatabase;

    private function usuario(): User
    {
        Mail::fake();
        return User::create(['name' => 'A', 'email' => 'a@a.com', 'password' => Hash::make('original1')]);
    }

    private function cambiar(): \Illuminate\Testing\TestResponse
    {
        return $this->postJson(route('change-password-after-code'), [
            'new_password' => 'tomada123',
            'new_password_confirmation' => 'tomada123',
        ]);
    }

    public function test_sin_verificar_el_codigo_no_cambia_la_contrasena(): void
    {
        $user = $this->usuario();

        $this->postJson(route('send-verify-code'), ['email' => 'a@a.com'])->assertOk();
        $this->postJson(route('verify-code'), ['verification_code' => '000000'])->assertStatus(422);
        $this->cambiar()->assertStatus(403);

        $this->assertTrue(Hash::check('original1', $user->fresh()->password));
    }

    public function test_con_el_codigo_correcto_cambia_una_sola_vez(): void
    {
        $user = $this->usuario();

        $this->postJson(route('send-verify-code'), ['email' => 'a@a.com'])->assertOk();
        // El código real viaja por mail; se reemplaza por uno conocido.
        $user->forceFill(['verification_code' => Hash::make('123456')])->save();

        $this->postJson(route('verify-code'), ['verification_code' => '123456'])->assertOk();
        $this->cambiar()->assertOk();
        $this->assertTrue(Hash::check('tomada123', $user->fresh()->password));

        // La verificación se consume: un segundo cambio ya no pasa.
        $this->cambiar()->assertStatus(403);
    }

    public function test_misma_respuesta_exista_o_no_el_correo(): void
    {
        $this->usuario();

        $existe   = $this->postJson(route('send-verify-code'), ['email' => 'a@a.com'])->json();
        $noExiste = $this->postJson(route('send-verify-code'), ['email' => 'nadie@a.com'])->json();

        $this->assertSame($existe, $noExiste);
    }
}
