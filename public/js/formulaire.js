document.addEventListener('DOMContentLoaded', function() {
    const selects = document.querySelectorAll('select');
    let typeEvenement = null;
    
    // Trouver le select du type d'événement
    selects.forEach(select => {
        const options = select.querySelectorAll('option');
        options.forEach(option => {
            if (option.value === 'barbecue') {
                typeEvenement = select;
            }
        });
    });
    
    const viandesContainer = document.getElementById('viandes-container');
    
    if (!typeEvenement || !viandesContainer) {
        console.error('Éléments non trouvés');
        return;
    }
    
    function toggleViandesField() {
        if (typeEvenement.value === 'barbecue') {
            viandesContainer.style.display = 'block';
        } else {
            viandesContainer.style.display = 'none';
        }
    }
    
    // Vérifier au chargement
    toggleViandesField();
    
    // Vérifier au changement
    typeEvenement.addEventListener('change', toggleViandesField);
});