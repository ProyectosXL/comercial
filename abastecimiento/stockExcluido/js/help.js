
// Help System JavaScript
$(document).ready(function() {
    // Initialize help system
    initializeHelp();
    
    // Add animation to help button on first load
    setTimeout(() => {
        $('.help-button').addClass('animate');
        setTimeout(() => {
            $('.help-button').removeClass('animate');
        }, 6000);
    }, 2000);
});

function initializeHelp() {
    // Track help modal events
    $('#helpModal').on('show.bs.modal', function() {
        // Analytics or tracking can be added here
        console.log('Help modal opened');
    });
    
    // Auto-expand first accordion in each tab when tab is shown
    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const targetTab = $(e.target.getAttribute('data-bs-target'));
        const firstAccordion = targetTab.find('.accordion-collapse').first();
        
        // Close all accordions in current tab first
        targetTab.find('.accordion-collapse').removeClass('show');
        targetTab.find('.accordion-button').addClass('collapsed');
        
        // Open first accordion
        firstAccordion.addClass('show');
        firstAccordion.siblings('.accordion-header').find('.accordion-button').removeClass('collapsed');
    });
    
    // Smooth scrolling for help sections
    $('.help-section a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        const target = $(this.getAttribute('href'));
        if (target.length) {
            target[0].scrollIntoView({
                behavior: 'smooth',
                block: 'start'
            });
        }
    });
    
    // Search functionality within help (if needed in future)
    setupHelpSearch();
}

function setupHelpSearch() {
    // Add search input to help modal if needed
    const searchHtml = `
        <div class="help-search mb-3" style="display: none;">
            <div class="input-group">
                <span class="input-group-text">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" class="form-control" id="helpSearchInput" placeholder="Buscar en la ayuda...">
                <button class="btn btn-outline-secondary" type="button" id="clearHelpSearch">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <small class="text-muted">Busque palabras clave en todas las secciones de ayuda</small>
        </div>
    `;
    
    // Can be activated when needed
    // $('.help-section').first().prepend(searchHtml);
}

// Function to highlight help sections based on current action
function highlightHelpSection(section) {
    const sectionMap = {
        'edit': 'nav-manage-tab',
        'import': 'nav-import-tab',
        'export': 'nav-export-tab',
        'delete': 'nav-manage-tab'
    };
    
    if (sectionMap[section]) {
        // Open help modal and navigate to relevant tab
        $('#helpModal').modal('show');
        setTimeout(() => {
            $(`#${sectionMap[section]}`).tab('show');
        }, 300);
    }
}

// Contextual help - called from main buttons
function showContextualHelp(context) {
    switch(context) {
        case 'edit':
            highlightHelpSection('edit');
            // Scroll to edit section
            setTimeout(() => {
                document.getElementById('collapseEdit').scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 500);
            break;
            
        case 'import':
            highlightHelpSection('import');
            break;
            
        case 'export':
            highlightHelpSection('export');
            break;
            
        case 'delete':
            highlightHelpSection('delete');
            setTimeout(() => {
                document.getElementById('collapseDelete').scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }, 500);
            break;
            
        default:
            $('#helpModal').modal('show');
    }
}

// Keyboard shortcuts for help
$(document).on('keydown', function(e) {
    // F1 key opens help
    if (e.key === 'F1') {
        e.preventDefault();
        $('#helpModal').modal('show');
    }
    
    // Ctrl + H also opens help
    if (e.ctrlKey && e.key === 'h') {
        e.preventDefault();
        $('#helpModal').modal('show');
    }
});

// Function to print help content
function printHelp() {
    // Expand all accordions before printing
    $('.help-accordion .accordion-collapse').addClass('show');
    $('.help-accordion .accordion-button').removeClass('collapsed');
    
    setTimeout(() => {
        window.print();
    }, 100);
}

// Auto-hide help button on small screens when scrolling
let helpButtonVisible = true;
$(window).on('scroll', function() {
    if ($(window).width() <= 768) {
        const scrollTop = $(window).scrollTop();
        const shouldHide = scrollTop > 100;
        
        if (shouldHide && helpButtonVisible) {
            $('.help-button').fadeOut(300);
            helpButtonVisible = false;
        } else if (!shouldHide && !helpButtonVisible) {
            $('.help-button').fadeIn(300);
            helpButtonVisible = true;
        }
    }
});

// Accessibility improvements
$(document).ready(function() {
    // Add ARIA labels
    $('.help-button').attr('aria-label', 'Abrir centro de ayuda');
    
    // Focus management for modal
    $('#helpModal').on('shown.bs.modal', function() {
        $(this).find('.nav-link.active').focus();
    });
    
    // Keyboard navigation for tabs
    $('.help-tabs .nav-link').on('keydown', function(e) {
        const $tabs = $('.help-tabs .nav-link');
        const currentIndex = $tabs.index(this);
        
        if (e.key === 'ArrowRight' && currentIndex < $tabs.length - 1) {
            e.preventDefault();
            $tabs.eq(currentIndex + 1).focus().tab('show');
        } else if (e.key === 'ArrowLeft' && currentIndex > 0) {
            e.preventDefault();
            $tabs.eq(currentIndex - 1).focus().tab('show');
        }
    });
});