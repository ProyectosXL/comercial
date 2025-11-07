/**
 * Helper para rotación de imágenes en visualizadores
 * Proporciona funcionalidad de rotación para Fancybox y otros visualizadores
 */

/**
 * Configura Fancybox con botones de rotación
 * @param {Array} images - Array de imágenes con formato {src, caption}
 * @param {Object} additionalOptions - Opciones adicionales para Fancybox
 * @returns {void}
 */
window.showFancyboxWithRotation = function(images, additionalOptions = {}) {
    const defaultOptions = {
        loop: false,
        Toolbar: {
            display: {
                left: [],
                middle: [],
                right: ['rotateLeft', 'rotateRight', 'zoom', 'close']
            }
        },
        on: {
            init: (fancybox) => {
                // Agregar botones de rotación personalizados
                const toolbar = fancybox.$container.querySelector('.fancybox__toolbar');
                
                // Botón rotar izquierda
                const btnRotateLeft = document.createElement('button');
                btnRotateLeft.className = 'f-button';
                btnRotateLeft.innerHTML = '<i class="bi bi-arrow-counterclockwise"></i>';
                btnRotateLeft.title = 'Rotar a la izquierda (90° sentido antihorario)';
                btnRotateLeft.onclick = () => {
                    const slide = fancybox.getSlide();
                    const img = slide.$el.querySelector('img');
                    if (img) {
                        let rotation = parseInt(img.dataset.rotation || '0');
                        rotation -= 90;
                        img.dataset.rotation = rotation;
                        img.style.transform = `rotate(${rotation}deg)`;
                    }
                };
                
                // Botón rotar derecha
                const btnRotateRight = document.createElement('button');
                btnRotateRight.className = 'f-button';
                btnRotateRight.innerHTML = '<i class="bi bi-arrow-clockwise"></i>';
                btnRotateRight.title = 'Rotar a la derecha (90° sentido horario)';
                btnRotateRight.onclick = () => {
                    const slide = fancybox.getSlide();
                    const img = slide.$el.querySelector('img');
                    if (img) {
                        let rotation = parseInt(img.dataset.rotation || '0');
                        rotation += 90;
                        img.dataset.rotation = rotation;
                        img.style.transform = `rotate(${rotation}deg)`;
                    }
                };
                
                // Insertar botones antes del botón de cerrar
                const closeButton = toolbar.querySelector('[data-fancybox-close]');
                if (closeButton) {
                    toolbar.insertBefore(btnRotateRight, closeButton);
                    toolbar.insertBefore(btnRotateLeft, btnRotateRight);
                }
            }
        },
        slideShow: { autoStart: false }
    };
    
    // Fusionar opciones
    const options = { ...defaultOptions, ...additionalOptions };
    
    // Fusionar callbacks si existen
    if (additionalOptions.on) {
        options.on = { ...defaultOptions.on, ...additionalOptions.on };
    }
    
    Fancybox.show(images, options);
};

/**
 * Agrega botones de rotación a un carrusel de Bootstrap
 * @param {string} carouselId - ID del carrusel (sin #)
 * @returns {void}
 */
window.addRotationToCarousel = function(carouselId) {
    const carousel = document.getElementById(carouselId);
    if (!carousel) return;
    
    // Crear contenedor de botones de rotación
    const rotationControls = document.createElement('div');
    rotationControls.className = 'rotation-controls';
    rotationControls.innerHTML = `
        <button class="btn btn-sm btn-outline-light rotate-btn" data-rotation="left" title="Rotar a la izquierda">
            <i class="bi bi-arrow-counterclockwise"></i>
        </button>
        <button class="btn btn-sm btn-outline-light rotate-btn" data-rotation="right" title="Rotar a la derecha">
            <i class="bi bi-arrow-clockwise"></i>
        </button>
    `;
    
    // Insertar botones en el carrusel
    carousel.appendChild(rotationControls);
    
    // Agregar event listeners
    rotationControls.querySelectorAll('.rotate-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const activeItem = carousel.querySelector('.carousel-item.active img');
            if (activeItem) {
                let rotation = parseInt(activeItem.dataset.rotation || '0');
                if (this.dataset.rotation === 'left') {
                    rotation -= 90;
                } else {
                    rotation += 90;
                }
                activeItem.dataset.rotation = rotation;
                activeItem.style.transform = `rotate(${rotation}deg)`;
            }
        });
    });
    
    // Resetear rotación al cambiar de slide
    carousel.addEventListener('slide.bs.carousel', function() {
        // Opcional: Resetear la rotación cuando cambias de imagen
        // const activeImg = carousel.querySelector('.carousel-item.active img');
        // if (activeImg) {
        //     activeImg.style.transform = 'rotate(0deg)';
        //     activeImg.dataset.rotation = '0';
        // }
    });
};

/**
 * Permite rotar una imagen individual con doble clic
 * @param {HTMLElement} img - Elemento de imagen
 * @returns {void}
 */
window.enableDoubleClickRotation = function(img) {
    img.addEventListener('dblclick', function() {
        let rotation = parseInt(this.dataset.rotation || '0');
        rotation += 90;
        this.dataset.rotation = rotation;
        this.style.transform = `rotate(${rotation}deg)`;
        this.style.transition = 'transform 0.3s ease';
    });
};
