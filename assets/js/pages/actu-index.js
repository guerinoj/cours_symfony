/**
 * Filtrage et recherche pour la page des actualités
 * Utilise les données fournies par le serveur (approche Symfony recommandée)
 */
class ActuIndexManager {
    constructor() {
        this.searchInput = document.getElementById("searchInput");
        this.categoryFilter = document.getElementById("categoryFilter");
        this.noResults = document.getElementById("noResults");
        this.postCards = document.querySelectorAll(".post-card");

        this.init();
    }

    init() {
        this.bindEvents();
        this.initializeState();
    }

    /**
     * Initialise l'état de la page
     */
    initializeState() {
        // Peut être utilisé pour des actions d'initialisation spécifiques
        this.updatePostCount();
    }

    /**
     * Attache les écouteurs d'événements
     */
    bindEvents() {
        this.searchInput.addEventListener("input", () => this.filterPosts());
        this.categoryFilter.addEventListener("change", () =>
            this.filterPosts()
        );

        // Optionnel : Reset des filtres avec Escape
        this.searchInput.addEventListener("keydown", (e) => {
            if (e.key === "Escape") {
                this.resetFilters();
            }
        });
    }

    /**
     * Fonction principale de filtrage
     */
    filterPosts() {
        const searchTerm = this.searchInput.value.toLowerCase().trim();
        const selectedCategory = this.categoryFilter.value.toLowerCase().trim();
        let visibleCount = 0;

        this.postCards.forEach((card) => {
            const title = card.dataset.title;
            const content = card.dataset.content;
            const cardCategories = card.dataset.categories;

            const matchesSearch =
                !searchTerm ||
                title.includes(searchTerm) ||
                content.includes(searchTerm);

            const matchesCategory =
                !selectedCategory || cardCategories.includes(selectedCategory);

            if (matchesSearch && matchesCategory) {
                card.style.display = "block";
                visibleCount++;
            } else {
                card.style.display = "none";
            }
        });

        this.toggleNoResultsMessage(visibleCount);
        this.updatePostCount(visibleCount);
    }

    /**
     * Affiche/cache le message "aucun résultat"
     */
    toggleNoResultsMessage(visibleCount) {
        if (visibleCount === 0 && this.postCards.length > 0) {
            this.noResults.style.display = "block";
        } else {
            this.noResults.style.display = "none";
        }
    }

    /**
     * Met à jour le compteur d'articles affichés
     */
    updatePostCount(filteredCount = null) {
        const countElement = document.querySelector(".text-muted");
        if (countElement) {
            const count =
                filteredCount !== null ? filteredCount : this.postCards.length;
            const total = this.postCards.length;

            if (filteredCount !== null && filteredCount !== total) {
                countElement.textContent = `${count} sur ${total} article${
                    total > 1 ? "s" : ""
                } affiché${count > 1 ? "s" : ""}`;
            } else {
                countElement.textContent = `${count} article${
                    count > 1 ? "s" : ""
                } publié${count > 1 ? "s" : ""}`;
            }
        }
    }

    /**
     * Remet à zéro tous les filtres
     */
    resetFilters() {
        this.searchInput.value = "";
        this.categoryFilter.value = "";
        this.filterPosts();
    }
}

// Initialisation quand le DOM est chargé
document.addEventListener("DOMContentLoaded", () => {
    new ActuIndexManager();
});
