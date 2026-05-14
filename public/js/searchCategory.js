document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('searchCategory');
    
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('.category-row');
            
            rows.forEach(row => {
                const categoryName = row.querySelector('.category-name')?.textContent.toLowerCase() || '';
                const categoryId = row.dataset.categoryId?.toLowerCase() || '';
                
                if (categoryName.includes(searchTerm) || categoryId.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
