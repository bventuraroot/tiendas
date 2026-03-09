/**
 * Mejoras del Menú
 * - Mantiene abiertos los submenús cuando hay elementos activos
 * - Mejora el comportamiento del menú en móviles
 * - Mejora la visualización del estado activo
 */

(function() {
  'use strict';

  // Variables para prevenir bucles infinitos
  let isProcessing = false;
  let observerActive = false;
  let menuInitialized = false;

  // Verificar si el script está deshabilitado en localStorage
  try {
    if (localStorage.getItem('menu-enhancements-disabled') === 'true') {
      return; // Salir silenciosamente si está deshabilitado
    }
  } catch(e) {
    // Continuar si hay error con localStorage
  }

  // Esperar a que el DOM esté listo
  document.addEventListener('DOMContentLoaded', function() {
    
    // Verificar que el menú exista antes de continuar
    const layoutMenu = document.getElementById('layout-menu');
    if (!layoutMenu) {
      return; // Salir si no hay menú en esta página
    }
    
    // Función para mantener abiertos los submenús con elementos activos
    function keepActiveSubmenusOpen() {
      // Prevenir ejecuciones múltiples simultáneas
      if (isProcessing) return;
      
      try {
        isProcessing = true;
        
        // layoutMenu ya está definido en el scope superior
        if (!layoutMenu) {
          isProcessing = false;
          return;
        }

        // Obtener la instancia del menú si está disponible
        const menuInstance = window.Helpers && window.Helpers.mainMenu ? window.Helpers.mainMenu : null;

        // Buscar todos los elementos de menú activos
        const activeMenuItems = layoutMenu.querySelectorAll('.menu-item.active');
        
        // Solo procesar si hay elementos activos
        if (activeMenuItems.length === 0) {
          isProcessing = false;
          return;
        }
        
        activeMenuItems.forEach(function(activeItem) {
          try {
            // Buscar el elemento padre que contiene submenús
            let currentElement = activeItem;
            let depth = 0;
            const maxDepth = 10; // Prevenir bucles infinitos
            
            // Subir en la jerarquía para encontrar el menu-item padre
            while (currentElement && depth < maxDepth) {
              depth++;
              currentElement = currentElement.parentElement;
              
              if (!currentElement) break;
              
              // Si encontramos un menu-sub, buscar el menu-item que lo contiene
              if (currentElement.classList.contains('menu-sub')) {
                // Buscar el menu-item padre que contiene este menu-sub
                let parentElement = currentElement.parentElement;
                let parentDepth = 0;
                
                while (parentElement && parentDepth < maxDepth) {
                  parentDepth++;
                  
                  if (parentElement.classList.contains('menu-item')) {
                    // Solo abrir si no está ya abierto para evitar bucles
                    // Y solo si el parent no tiene un enlace que navegue (solo menús que expanden)
                    const parentLink = parentElement.querySelector('.menu-toggle');
                    if (!parentElement.classList.contains('open') && parentLink) {
                      // Verificar que no sea un enlace de navegación real
                      const href = parentLink.getAttribute('href');
                      if (!href || href === '#' || href === 'javascript:void(0);' || href.includes('javascript:')) {
                        // Si tenemos la instancia del menú, usar su método open
                        if (menuInstance && typeof menuInstance.open === 'function') {
                          try {
                            menuInstance.open(parentLink, false);
                          } catch(e) {
                            // Si falla, usar método manual como fallback
                            parentElement.classList.add('open');
                            const menuSub = parentElement.querySelector('.menu-sub');
                            if (menuSub) {
                              menuSub.style.display = '';
                              menuSub.classList.add('show');
                            }
                            parentLink.setAttribute('aria-expanded', 'true');
                          }
                        } else {
                          // Método manual si no tenemos la instancia
                          parentElement.classList.add('open');
                          const menuSub = parentElement.querySelector('.menu-sub');
                          if (menuSub) {
                            menuSub.style.display = '';
                            menuSub.classList.add('show');
                          }
                          parentLink.setAttribute('aria-expanded', 'true');
                        }
                      }
                    }
                    break;
                  }
                  parentElement = parentElement.parentElement;
                }
                break;
              }
              
              // Si llegamos al menu-inner, detener
              if (currentElement.classList.contains('menu-inner')) {
                break;
              }
            }
          } catch(e) {
            console.warn('Error procesando elemento activo:', e);
          }
        });
      } catch(e) {
        console.error('Error en keepActiveSubmenusOpen:', e);
      } finally {
        // Resetear el flag después de un pequeño delay para permitir que termine cualquier operación
        setTimeout(function() {
          isProcessing = false;
        }, 100);
      }
    }

    // Función para mejorar el comportamiento del menú en móviles
    function improveMobileMenuBehavior() {
      // layoutMenu ya está definido en el scope superior
      if (!layoutMenu) return;

      // En móviles, no cerrar el menú inmediatamente al hacer clic en un enlace
      const menuLinks = layoutMenu.querySelectorAll('.menu-link:not(.menu-toggle)');
      
      menuLinks.forEach(function(link) {
        // Evitar agregar listeners múltiples veces
        if (link.dataset.menuEnhanced === 'true') return;
        link.dataset.menuEnhanced = 'true';
        
        link.addEventListener('click', function(e) {
          try {
            // Solo aplicar en pantallas pequeñas
            if (window.innerWidth < 1200) {
              // Agregar una clase para indicar que se está navegando
              this.classList.add('navigating');
              
              // Mantener el menú abierto un poco más para que el usuario vea la transición
              // El menú se cerrará automáticamente después de la navegación por el overlay
              setTimeout(function() {
                if (link && link.classList) {
                  link.classList.remove('navigating');
                }
              }, 300);
            }
          } catch(e) {
            console.warn('Error en improveMobileMenuBehavior:', e);
          }
        });
      });
    }

    // Función para mejorar la visualización del estado activo
    function enhanceActiveStateVisibility() {
      // layoutMenu ya está definido en el scope superior
      if (!layoutMenu) return;

      // Agregar clase adicional a los elementos activos para mejor visualización
      const activeItems = layoutMenu.querySelectorAll('.menu-item.active');
      
      activeItems.forEach(function(item) {
        const menuLink = item.querySelector('.menu-link');
        if (menuLink) {
          menuLink.classList.add('active-highlight');
        }
      });
    }

    // Función para manejar la navegación y mantener el estado
    function handleMenuNavigation() {
      // layoutMenu ya está definido en el scope superior
      if (!layoutMenu) return;

      // Guardar el estado del menú en localStorage cuando se abre/cierra un submenu
      const menuToggles = layoutMenu.querySelectorAll('.menu-toggle');
      
      menuToggles.forEach(function(toggle) {
        // Evitar agregar listeners múltiples veces
        if (toggle.dataset.menuNavigationEnhanced === 'true') return;
        toggle.dataset.menuNavigationEnhanced = 'true';
        
        toggle.addEventListener('click', function() {
          try {
            setTimeout(function() {
              const parentItem = toggle.closest('.menu-item');
              if (parentItem) {
                const isOpen = parentItem.classList.contains('open');
                const menuLink = parentItem.querySelector('.menu-link');
                const menuId = menuLink ? menuLink.textContent.trim() : '';
                if (menuId) {
                  try {
                    localStorage.setItem('menu-state-' + menuId, isOpen ? 'open' : 'closed');
                  } catch(e) {
                    // Ignorar errores de localStorage
                  }
                }
              }
            }, 100);
          } catch(e) {
            console.warn('Error en handleMenuNavigation:', e);
          }
        });
      });
    }

    // Ejecutar mejoras después de un delay para asegurar que el menú esté inicializado
    // Esperar a que main.js haya terminado de inicializar el menú
    if (!menuInitialized) {
      menuInitialized = true;
      
      setTimeout(function() {
        try {
          keepActiveSubmenusOpen();
          improveMobileMenuBehavior();
          enhanceActiveStateVisibility();
          handleMenuNavigation();
        } catch(e) {
          console.error('Error inicializando mejoras del menú:', e);
        }
      }, 500); // Aumentar delay para asegurar que todo esté listo
    }

    // Ejecutar también cuando se redimensiona la ventana (pero solo si no estamos procesando)
    let resizeTimeout = null;
    window.addEventListener('resize', function() {
      if (resizeTimeout) {
        clearTimeout(resizeTimeout);
      }
      resizeTimeout = setTimeout(function() {
        try {
          if (!isProcessing) {
            keepActiveSubmenusOpen();
          }
        } catch(e) {
          console.warn('Error en resize:', e);
        }
      }, 200);
    });

    // NO usar MutationObserver para evitar interferir con la navegación del usuario
    // El menú ya se actualiza correctamente cuando cambia la página
    // Solo necesitamos asegurar que los submenús estén abiertos al cargar la página
    
    // Ejecutar una vez más después de que la página haya cargado completamente
    // para asegurar que los estados activos se reflejen correctamente
    window.addEventListener('load', function() {
      setTimeout(function() {
        try {
          if (!isProcessing) {
            keepActiveSubmenusOpen();
            enhanceActiveStateVisibility();
          }
        } catch(e) {
          console.warn('Error en window.load:', e);
        }
      }, 200);
    });
  });
})();

