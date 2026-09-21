/* ================================================
   DIRECCIONARIO - JS (Adaptado para PHP en COMER)
   Patron: IIFE + objeto App
   Replicando fielmente funcionalidad y visual de Python
   ================================================ */
;(function ($) {
  'use strict';

  var CANAL_ICONS = {
    'MAYORISTA':    'bi-bag-check-fill',
    'FRANQUICIA':   'bi-shop',
    'PROPIO':       'bi-building',
    'PROPIOS':      'bi-building',
    'ONLINE':       'bi-globe2',
    'DISTRIBUIDOR': 'bi-truck',
  };

  var CANAL_COLOR_CLASSES = [
    'canal-0', 'canal-1', 'canal-2', 'canal-3', 'canal-4', 'canal-5'
  ];

  var App = {

    /* Estado */
    state: {
      sucursales:       [],
      filtered:         [],
      view:             'cards',  // 'cards' | 'list'
      canal:            '',
      provincia:        '',
      tipo_local:       '',
      grupo_empresario: '',
      busqueda:         '',
      mostrar_todos:    false,
    },

    canalColorMap: {},
    canalColorIdx: 0,
    ajaxUrl:       'controller/direccionario_ajax.php',

    /* -------- INIT -------- */
    init: function () {
      this.ajaxUrl = (window.DIRECCIONARIO_CONFIG || {}).ajaxUrl || 'controller/direccionario_ajax.php';
      this._bindEvents();
      this._fetchData();
    },

    /* -------- BIND EVENTS -------- */
    _bindEvents: function () {
      var self = this;

      /* Busqueda con debounce: busca a partir de 3 caracteres (o al vaciar el campo) */
      var debouncedSearch = this._debounce(function () {
        var val = $('#dir-search').val();
        if (val.length > 0 && val.length < 3) {
          return;
        }
        self.state.busqueda = val;
        self._fetchData();
      }, 300);
      $('#dir-search').on('input', debouncedSearch);

      /* Clear search */
      $('#dir-clear-search').on('click', function () {
        $('#dir-search').val('');
        self.state.busqueda = '';
        self._fetchData();
      });

      /* Filtros canal */
      $(document).on('click', '.dir-filter-btn[data-filter="canal"]', function () {
        self.state.canal = $(this).data('canal');
        $('.dir-filter-btn[data-filter="canal"]').removeClass('active');
        $(this).addClass('active');
        self._fetchData();
      });

      /* Filtros provincia */
      $(document).on('click', '.dir-filter-btn[data-filter="provincia"]', function () {
        self.state.provincia = $(this).data('prov');
        $('.dir-filter-btn[data-filter="provincia"]').removeClass('active');
        $(this).addClass('active');
        self._fetchData();
      });

      /* Filtros tipo local */
      $(document).on('click', '.dir-filter-btn[data-filter="tipo_local"]', function () {
        self.state.tipo_local = $(this).data('tlocal');
        $('.dir-filter-btn[data-filter="tipo_local"]').removeClass('active');
        $(this).addClass('active');
        self._fetchData();
      });

      /* Filtros grupo empresario */
      $(document).on('click', '.dir-filter-btn[data-filter="grupo"]', function () {
        self.state.grupo_empresario = $(this).data('grupo');
        $('.dir-filter-btn[data-filter="grupo"]').removeClass('active');
        $(this).addClass('active');
        self._fetchData();
      });

      /* Toggle mostrar todas vs solo principales */
      $(document).on('click', '#dir-toggle-todas', function () {
        self.state.mostrar_todos = !self.state.mostrar_todos;
        $(this).toggleClass('dir-toggle-todas-on', self.state.mostrar_todos);
        $(this).attr('title', self.state.mostrar_todos
          ? 'Mostrando TODAS las sucursales — click para ver solo principales'
          : 'Mostrando solo sucursales principales — click para ver todas');
        var $label = $('#dir-toggle-todas-label');
        $label.text(self.state.mostrar_todos ? 'Ver solo principales' : 'Ver todas');
        self._fetchData();
      });

      /* Reset all */
      $(document).on('click', '#dir-reset-all, #dir-reset-empty', function () {
        self.resetFilters();
      });

      /* Toggle vista */
      $('#dir-toggle-cards').on('click', function () { self._setView('cards'); });
      $('#dir-toggle-list').on('click',  function () { self._setView('list');  });

      /* Export Excel */
      $('#dir-export-excel').on('click', function () { self._exportExcel(); });

      /* Copy email / dns / key */
      $(document).on('click', '.dir-copy-btn', function () {
        self._copyToClipboard($(this).data('mail'));
      });

      /* Mobile sidebar */
      $('#dir-hamburger').on('click', function () {
        $('#dir-sidebar').addClass('open');
        $('#dir-overlay').addClass('open');
      });
      $(document).on('click', '#dir-sidebar-close, #dir-overlay', function () {
        $('#dir-sidebar').removeClass('open');
        $('#dir-overlay').removeClass('open');
      });

      /* Acordeones de filtros con chevrons (Bootstrap 5) */
      $(document).on('hide.bs.collapse', function (e) {
        $('[data-bs-target="#' + e.target.id + '"], [data-target="#' + e.target.id + '"]').attr('aria-expanded', 'false');
      });
      $(document).on('show.bs.collapse', function (e) {
        $('[data-bs-target="#' + e.target.id + '"], [data-target="#' + e.target.id + '"]').attr('aria-expanded', 'true');
      });
    },

    /* -------- FETCH AJAX -------- */
    _fetchData: function () {
      var self = this;
      this._showLoading(true);

      $.ajax({
        url:  this.ajaxUrl,
        type: 'POST',
        data: {
          action:           'buscar',
          busqueda:         this.state.busqueda,
          canal:            this.state.canal,
          provincia:        this.state.provincia,
          tipo_local:       this.state.tipo_local,
          grupo_empresario: this.state.grupo_empresario,
          mostrar_todos:    this.state.mostrar_todos ? 'true' : 'false'
        },
        dataType: 'json',
        success: function (res) {
          self.state.sucursales = res.sucursales || [];
          self.state.filtered   = self.state.sucursales.slice();
          self._buildColorMap();
          self._render();
        },
        error: function () {
          Swal.fire({
            icon: 'error',
            title: 'Error de conexión',
            text: 'No se pudieron cargar los datos. Reintentar más tarde.',
          });
          self._showLoading(false);
        },
      });
    },

    /* -------- RENDER -------- */
    _render: function () {
      var data = this.state.filtered;
      this._updateCounter(data.length);

      if (!data.length) {
        $('#dir-empty').show();
        $('#dir-view-cards, #dir-view-list').hide();
        this._showLoading(false);
        return;
      }
      $('#dir-empty').hide();

      if (this.state.view === 'cards') {
        this._renderCards(data);
        $('#dir-view-cards').show();
        $('#dir-view-list').hide();
      } else {
        this._renderList(data);
        $('#dir-view-list').show();
        $('#dir-view-cards').hide();
      }
      this._showLoading(false);
    },

    /* -------- RENDER CARDS -------- */
    _renderCards: function (data) {
      var self  = this;
      var $grid = $('#dir-cards-grid').empty();

      $.each(data, function (i, d) {
        var delay      = Math.min(i * 35, 350);
        var colorIdx   = self.canalColorMap[d.canal] || 'canal-0';
        var icon       = self._getCanalIcon(d.canal);
        var mapsHref   = self._mapsUrl(d);
        var cfg        = window.DIRECCIONARIO_CONFIG || {};

        var boolIcon = function(val) {
          return (val === 'SI' || val === 'True' || val === '1' || val === 'true' || val === true)
            ? '<i class="fas fa-check-circle" style="color:#38a169"></i>'
            : '<i class="fas fa-times-circle" style="color:#e53e3e"></i>';
        };

        /* ── Filas del body ── */
        var bodyRows = '';
        bodyRows += '<div class="sc-info-row">'
          + '<i class="fas fa-map-marker-alt"></i>'
          + '<span>' + self._esc(d.localidad) + (d.provincia ? ', ' + self._esc(d.provincia) : '') + '</span>'
          + '</div>';
        bodyRows += '<div class="sc-info-row">'
          + '<i class="fas fa-road"></i>'
          + '<span>' + self._esc(d.direccion || '-') + '</span>'
          + '</div>';
        if (d.telefono) {
          bodyRows += '<div class="sc-info-row">'
            + '<i class="fas fa-phone"></i>'
            + '<span>' + self._esc(d.telefono) + '</span>'
            + '</div>';
        }
        if (d.mail) {
          bodyRows += '<div class="sc-info-row">'
            + '<i class="fas fa-envelope"></i>'
            + '<a href="mailto:' + self._esc(d.mail) + '">' + self._esc(d.mail) + '</a>'
            + '</div>';
        }
        if (d.horario) {
          bodyRows += '<div class="sc-info-row">'
            + '<i class="fas fa-clock"></i>'
            + '<span>' + self._esc(d.horario) + '</span>'
            + '</div>';
        }
        bodyRows += '<div class="sc-info-row">'
          + '<i class="fas fa-calendar-week"></i>'
          + '<span>' + self._esc(d.dias_abiertos || 'Cerrado') + '</span>'
          + '</div>';

        /* ── Datos adicionales (colapsable) ── */
        var collapseId = 'sc-extra-' + d.nro_sucursal;
        var extraRows  = '';

        if (d.cod_client) {
          extraRows += '<div class="sc-extra-row"><span class="sc-extra-lbl">Cód. cliente</span>'
            + '<span>' + self._esc(d.cod_client) + '</span></div>';
        }
        if (d.tipo_local) {
          extraRows += '<div class="sc-extra-row"><span class="sc-extra-lbl">Tipo de local</span>'
            + '<span>' + self._esc(d.tipo_local) + '</span></div>';
        }
        if (d.grupo_empresario) {
          extraRows += '<div class="sc-extra-row"><span class="sc-extra-lbl">Grupo</span>'
            + '<span>' + self._esc(d.grupo_empresario) + '</span></div>';
        }
        if (d.supervisora) {
          extraRows += '<div class="sc-extra-row"><span class="sc-extra-lbl">Supervisora</span>'
            + '<span><i class="fas fa-user-tie mr-1 text-muted"></i>' + self._esc(d.supervisora) + '</span></div>';
        }
        extraRows += '<div class="sc-extra-row">'
          + '<span class="sc-extra-lbl">Tango</span><span>' + boolIcon(d.tango) + '</span>'
          + '<span class="sc-extra-lbl sc-extra-lbl-sep">Tienda</span><span>' + boolIcon(d.tienda) + '</span>'
          + '</div>';
        extraRows += '<div class="sc-extra-row">'
          + '<span class="sc-extra-lbl">Vtex</span><span>' + boolIcon(d.integra_vtex) + '</span>'
          + '<span class="sc-extra-lbl sc-extra-lbl-sep">Depósito</span><span>' + self._esc(d.deposito || '-') + '</span>'
          + '</div>';
        if (d.retiro_expres) {
          extraRows += '<div class="sc-extra-row"><span class="sc-extra-lbl">Retiro express</span>'
            + '<span>' + boolIcon(d.retiro_expres) + '</span></div>';
        }
        if (d.nro_sucursal_madre) {
          extraRows += '<div class="sc-extra-row"><span class="sc-extra-lbl">Suc. madre</span>'
            + '<span>' + self._esc(d.nro_sucursal_madre) + '</span></div>';
        }
        if (d.nro_sucursal_anterior) {
          extraRows += '<div class="sc-extra-row"><span class="sc-extra-lbl">Suc. anterior</span>'
            + '<span>' + self._esc(d.nro_sucursal_anterior) + '</span></div>';
        }

        // Campos técnicos
        if (cfg.canVerTecnico) {
          if (d.base_nombre) {
            extraRows += '<div class="sc-extra-row sc-extra-tec"><span class="sc-extra-lbl">'
              + '<i class="fas fa-database mr-1"></i>Base</span>'
              + '<span>' + self._esc(d.base_nombre) + '</span></div>';
          }
          if (d.conexion_dns) {
            extraRows += '<div class="sc-extra-row sc-extra-tec"><span class="sc-extra-lbl">'
              + '<i class="fas fa-network-wired mr-1"></i>DNS</span>'
              + '<span>' + self._esc(d.conexion_dns)
              + ' <button class="dir-copy-btn" data-mail="' + self._esc(d.conexion_dns) + '" title="Copiar">'
              + '<i class="fas fa-copy"></i></button></span></div>';
          }
          if (d.n_llave_tango) {
            extraRows += '<div class="sc-extra-row sc-extra-tec"><span class="sc-extra-lbl">'
              + '<i class="fas fa-key mr-1"></i>Llave</span>'
              + '<span>' + self._esc(d.n_llave_tango)
              + ' <button class="dir-copy-btn" data-mail="' + self._esc(d.n_llave_tango) + '" title="Copiar">'
              + '<i class="fas fa-copy"></i></button></span></div>';
          }
        }

        /* ── Botones de edición según permisos ── */
        var editBtns = '';
        if (cfg.canEdit || cfg.canEditBasic) {
          editBtns = '<a href="AltaSucursal.php?id=' + d.nro_sucursal + '"'
            + ' class="sc-btn-icon" title="Editar sucursal"><i class="fas fa-pencil-alt"></i></a>';
        }

        /* ── Botón clonar ── */
        var cloneBtn = '';
        if (cfg.canEdit) {
          cloneBtn = '<a href="AltaSucursal.php?clonar=' + d.nro_sucursal + '"'
            + ' class="sc-btn-icon" title="Clonar sucursal"><i class="fas fa-clone"></i></a>';
        }

        /* ── Botón eliminar ── */
        var deleteBtns = '';
        if (cfg.canDelete) {
          deleteBtns = '<button class="sc-btn-icon sc-btn-delete dir-delete-btn"'
            + ' data-id="' + d.nro_sucursal + '"'
            + ' data-nombre="' + self._esc(d.desc_sucursal) + '"'
            + ' title="Eliminar sucursal" style="color:#e53e3e"><i class="fas fa-trash-alt"></i></button>';
        }

        /* ── Botón de copiar email ── */
        var copyMailBtn = d.mail
          ? '<button class="sc-btn-icon dir-copy-btn" data-mail="' + self._esc(d.mail) + '" title="Copiar email">'
            + '<i class="fas fa-envelope"></i></button>'
          : '';

        /* ── Construir card ── */
        var card = '<div class="sucursal-card" style="animation-delay:' + delay + 'ms">'

          /* Header con gradiente */
          + '<div class="sc-header sc-canal-' + colorIdx + '">'
          +   '<span class="sc-canal-badge">' + self._esc(d.canal || 'S/C') + '</span>'
          +   '<div class="sc-icon-wrap"><i class="bi ' + icon + '"></i></div>'
          +   '<p class="sc-name">' + self._esc(d.desc_sucursal) + '</p>'
          +   '<span class="sc-nro">#' + d.nro_sucursal + '</span>'
          + '</div>'

          /* Body */
          + '<div class="sc-body">'
          +   bodyRows
          + '</div>'

          /* Datos adicionales colapsables */
          + '<div class="sc-collapse collapse" id="' + collapseId + '">'
          +   extraRows
          + '</div>'

          /* Footer */
          + '<div class="sc-footer">'
          +   '<a href="' + mapsHref + '" target="_blank" rel="noopener noreferrer" class="sc-btn-maps">'
          +     '<i class="fas fa-map-marker-alt"></i> Ver en Maps</a>'
          +   '<div class="sc-footer-actions">'
          +     copyMailBtn
          +     '<button class="sc-btn-icon sc-btn-toggle" data-bs-toggle="collapse" data-bs-target="#' + collapseId + '"'
          +       ' aria-expanded="false" title="Ver datos adicionales">'
          +       '<i class="fas fa-chevron-down sc-chevron"></i>'
          +     '</button>'
          +     editBtns
          +     cloneBtn
          +     deleteBtns
          +   '</div>'
          + '</div>'

          + '</div>';

        $grid.append(card);
      });
    },

    /* -------- RENDER LIST -------- */
    _renderList: function (data) {
      var self   = this;
      var $tbody = $('#dir-list-tbody').empty();

      $.each(data, function (_, d) {
        var mailCell = d.mail
          ? '<a href="mailto:' + self._esc(d.mail) + '">' + self._esc(d.mail) + '</a>'
          : '-';
        var mapsHref = self._mapsUrl(d);

        var tr = '<tr>'
          + '<td><strong>#' + d.nro_sucursal + '</strong></td>'
          + '<td><span class="fw-semibold">' + self._esc(d.desc_sucursal) + '</span></td>'
          + '<td><span class="badge bg-secondary-subtle text-secondary-emphasis rounded-pill">' + self._esc(d.canal || '—') + '</span></td>'
          + '<td>' + self._esc(d.localidad || '—') + '</td>'
          + '<td>' + self._esc(d.provincia || '—') + '</td>'
          + '<td>' + self._esc(d.direccion || '—') + '</td>'
          + '<td>' + self._esc(d.telefono || '—') + '</td>'
          + '<td>' + mailCell + '</td>'
          + '<td><small>' + self._esc(d.horario || '—') + '</small></td>'
          + '<td class="text-center">'
          +   '<a href="' + mapsHref + '" target="_blank" rel="noopener noreferrer" class="dir-btn-maps-sm btn btn-sm" title="Ver en Maps">'
          +     '<i class="fas fa-map-marker-alt"></i>'
          +   '</a>'
          + '</td>'
          + '</tr>';

        $tbody.append(tr);
      });
    },

    /* -------- MAPS URL -------- */
    _mapsUrl: function (d) {
      var cleanAddr = (d.direccion || '')
        .replace(/\s*\(.*?\)/g, '')
        .replace(/\s*-\s+.*/g, '')
        .replace(/\s+L\d+[\d\/\w]*\s*$/i, '')
        .trim();
      var parts = [cleanAddr, d.localidad, d.provincia].filter(Boolean);
      return 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(parts.join(', '));
    },

    /* -------- COLOR MAP BUILDER -------- */
    _buildColorMap: function () {
      var self = this;
      var uniqueCanales = [];
      $.each(this.state.sucursales, function (_, s) {
        if (s.canal && uniqueCanales.indexOf(s.canal) === -1) {
          uniqueCanales.push(s.canal);
        }
      });
      self.canalColorMap = {};
      $.each(uniqueCanales, function (i, c) {
        self.canalColorMap[c] = CANAL_COLOR_CLASSES[i % CANAL_COLOR_CLASSES.length];
      });
    },

    /* -------- HELPERS -------- */
    _getCanalIcon: function (canal) {
      var key = (canal || '').toUpperCase();
      for (var k in CANAL_ICONS) {
        if (key.indexOf(k) !== -1) return CANAL_ICONS[k];
      }
      return 'bi-building';
    },

    _updateCounter: function (count) {
      $('#dir-count').text(count + (count === 1 ? ' sucursal' : ' sucursales'));
    },

    _showLoading: function (show) {
      if (show) {
        $('#dir-loading').show();
        $('#dir-content').hide();
      } else {
        $('#dir-loading').hide();
        $('#dir-content').show();
      }
    },

    _setView: function (view) {
      this.state.view = view;
      $('.dir-view-btn').removeClass('active');
      $('#dir-toggle-' + view).addClass('active');
      this._render();
    },

    _copyToClipboard: function (text) {
      if (!text) return;
      var self = this;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(function () {
          self._toast('Copiado al portapapeles', 'success');
        });
      } else {
        var $temp = $('<input>').val(text).appendTo('body').select();
        document.execCommand('copy');
        $temp.remove();
        self._toast('Copiado al portapapeles', 'success');
      }
    },

    _toast: function (msg, icon) {
      if (window.Swal) {
        Swal.fire({
          toast: true,
          position: 'top-end',
          icon: icon || 'success',
          title: msg,
          showConfirmButton: false,
          timer: 2000,
          timerProgressBar: true
        });
      }
    },

    _debounce: function (func, wait) {
      var timeout;
      return function () {
        var context = this, args = arguments;
        clearTimeout(timeout);
        timeout = setTimeout(function () {
          func.apply(context, args);
        }, wait);
      };
    },

    _esc: function (str) {
      if (!str) return '';
      return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    },

    resetFilters: function () {
      this.state.busqueda         = '';
      this.state.canal            = '';
      this.state.provincia        = '';
      this.state.tipo_local       = '';
      this.state.grupo_empresario = '';
      $('#dir-search').val('');
      $('.dir-filter-btn').removeClass('active');
      $('.dir-filter-btn[data-canal=""], .dir-filter-btn[data-prov=""], .dir-filter-btn[data-tlocal=""], .dir-filter-btn[data-grupo=""]').addClass('active');
      this._fetchData();
    },

    /* -------- EXPORT EXCEL (SheetJS) -------- */
    _exportExcel: function () {
      var data = this.state.filtered;
      if (!data || !data.length) {
        this._toast('No hay datos para exportar', 'warning');
        return;
      }
      var rows = data.map(function (s) {
        return {
          'Nro Sucursal':     s.nro_sucursal,
          'Cód. Cliente':     s.cod_client,
          'Nombre':           s.desc_sucursal,
          'Canal':            s.canal,
          'Tipo Local':       s.tipo_local,
          'Grupo':            s.grupo_empresario,
          'Dirección':        s.direccion,
          'Localidad':        s.localidad,
          'Provincia':        s.provincia,
          'Teléfono':         s.telefono,
          'Email':            s.mail,
          'Horario':          s.horario,
          'Días Abierto':     s.dias_abiertos,
          'Supervisora':      s.supervisora,
          'Tango':            s.tango,
          'Tienda':           s.tienda,
          'VTEX':             s.integra_vtex,
          'Depósito':         s.deposito
        };
      });

      var ws = XLSX.utils.json_to_sheet(rows);
      var wb = XLSX.utils.book_new();
      XLSX.utils.book_append_sheet(wb, ws, 'Sucursales');
      var fecha = new Date().toISOString().slice(0, 10);
      XLSX.writeFile(wb, 'Direccionario_Sucursales_' + fecha + '.xlsx');
    }
  };

  $(document).ready(function () {
    App.init();
  });

})(jQuery);
