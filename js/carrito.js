/**
 * Mitos Escénicos - Helpers de carrito (añadir desde tienda vía AJAX)
 */
(function () {
  'use strict';

  var baseUrl = (document.querySelector('script[data-base-url]') && document.querySelector('script[data-base-url]').getAttribute('data-base-url')) || '';
  var apiBase = baseUrl ? baseUrl.replace(/\/$/, '') + '/' : '';

  function addToCart(formData, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', apiBase + 'api/carrito-add.php');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function () {
      try {
        var json = JSON.parse(xhr.responseText);
        if (callback) callback(json);
      } catch (e) {
        if (callback) callback({ ok: false, error: 'Error de respuesta' });
      }
    };
    xhr.onerror = function () {
      if (callback) callback({ ok: false, error: 'Error de red' });
    };
    xhr.send(new URLSearchParams(formData).toString());
  }

  window.mitosCarrito = {
    agregarMercancia: function (mercanciaId, nombre, precio, cantidad) {
      addToCart({
        tipo: 'mercancia',
        mercancia_id: mercanciaId,
        precio: precio,
        cantidad: cantidad || 1
      }, function (res) {
        if (res.ok) {
          if (typeof window.mitosCarrito.onAdd === 'function') {
            window.mitosCarrito.onAdd(res);
          }
          if (window.location.pathname.indexOf('carrito') === -1) {
            window.location.href = apiBase + 'carrito.php';
          } else {
            window.location.reload();
          }
        } else {
          alert(res.error || 'No se pudo añadir al carrito');
        }
      });
    },
    onAdd: null
  };
})();
