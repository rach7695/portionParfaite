function copyToClipboard() {
    // Récupérer les données depuis les attributs data du bouton
    const btn = document.getElementById('copyBtn');
    const typeEvenement = btn.dataset.type;
    const nbPersonnes = btn.dataset.nbPersonnes;
    const nbEnfants = btn.dataset.nbEnfants;
    const totalPersonnes = btn.dataset.totalPersonnes;
    const dateEvenement = btn.dataset.dateEvenement;
    
    let text = `LISTE POUR ${typeEvenement.toUpperCase()}\n`;
    text += "=".repeat(50) + "\n\n";
    text += `Nombre d'adultes : ${nbPersonnes}\n`;
    
    if (nbEnfants > 0) {
        text += `Nombre d'enfants : ${nbEnfants}\n`;
    }
    
    text += `Total invités : ${totalPersonnes}\n`;
    
    if (dateEvenement) {
        text += `Date : ${dateEvenement}\n`;
    }
    
    text += "\n";
    
    // Récupérer les données du tableau
    const categories = document.querySelectorAll('.category-header');
    categories.forEach(category => {
        const categoryName = category.textContent.trim();
        text += `\n📦 ${categoryName}\n`;
        text += "-".repeat(50) + "\n";
        
        let nextRow = category.parentElement.nextElementSibling;
        while (nextRow && !nextRow.querySelector('.category-header')) {
            const cols = nextRow.querySelectorAll('td');
            if (cols.length >= 2) {
                const produit = cols[0].textContent.trim();
                const quantite = cols[1].textContent.trim();
                text += `• ${produit} : ${quantite}\n`;
            }
            nextRow = nextRow.nextElementSibling;
        }
    });
    
    navigator.clipboard.writeText(text).then(function() {
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check"></i> Copié !';
        btn.classList.remove('btn-outline-info');
        btn.classList.add('btn-success');
        
        setTimeout(function() {
            btn.innerHTML = originalText;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-info');
        }, 2000);
    });
}