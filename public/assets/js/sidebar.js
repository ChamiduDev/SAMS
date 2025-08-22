class SidebarManager {
    constructor() {
        this.sidebar = document.getElementById('sidebar');
        this.toggleButton = document.getElementById('sidebarToggle');
        this.closeButton = document.querySelector('#sidebar .btn-close');
        this.init();
    }

    init() {
        if (!this.sidebar || !this.toggleButton) return;

        // Initialize Bootstrap's Offcanvas
        this.offcanvas = new bootstrap.Offcanvas(this.sidebar);

        this.addEventListeners();
    }

    addEventListeners() {
        // Toggle button click
        this.toggleButton.addEventListener('click', (e) => {
            e.preventDefault();
            this.toggle();
        });

        // Close button click
        if (this.closeButton) {
            this.closeButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.hide();
            });
        }

        // Close on outside click
        document.addEventListener('click', (e) => {
            if (this.isShown() &&
                !this.sidebar.contains(e.target) &&
                !this.toggleButton.contains(e.target)) {
                this.hide();
            }
        });

        // Close on navigation (mobile)
        const navLinks = this.sidebar.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    this.hide();
                }
            });
        });
    }

    toggle() {
        if (this.isShown()) {
            this.hide();
        } else {
            this.show();
        }
    }

    show() {
        this.offcanvas.show();
    }

    hide() {
        this.offcanvas.hide();
    }

    isShown() {
        return this.sidebar.classList.contains('show');
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new SidebarManager();
});
