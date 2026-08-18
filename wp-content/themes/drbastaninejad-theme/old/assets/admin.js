jQuery(function ($) {
  'use strict';

  $(document).on('click', '.drb-select-media', function (event) {
    event.preventDefault();
    var button = $(this);
    var field = button.closest('.drb-media-field');
    var frame = wp.media({ title: 'انتخاب تصویر', button: { text: 'استفاده از تصویر' }, multiple: false });
    frame.on('select', function () {
      var item = frame.state().get('selection').first().toJSON();
      field.find('input[type=hidden]').val(item.id);
      field.find('.drb-media-preview').html('<img src="' + (item.sizes && item.sizes.medium ? item.sizes.medium.url : item.url) + '" alt="">');
      field.find('.drb-remove-media').prop('disabled', false);
    });
    frame.open();
  });

  $(document).on('click', '.drb-remove-media', function (event) {
    event.preventDefault();
    var field = $(this).closest('.drb-media-field');
    field.find('input[type=hidden]').val('');
    field.find('.drb-media-preview').html('<span>بدون تصویر</span>');
    $(this).prop('disabled', true);
  });

  function syncGallery(editor) {
    var items = [];
    editor.find('.drb-case-gallery-row').each(function () {
      var row = $(this);
      items.push({ id: Number(row.attr('data-id')) || 0, label: row.find('.drb-case-gallery-label').val() || '', interval: row.find('.drb-case-gallery-interval').val() || '' });
    });
    editor.find('.drb-case-gallery-json').val(JSON.stringify(items));
    editor.find('.drb-case-gallery-empty').prop('hidden', items.length > 0);
  }

  function galleryRow(item) {
    var source = item.sizes && item.sizes.thumbnail ? item.sizes.thumbnail.url : item.url;
    var label = item.caption || item.title || '';
    return $('<div class="drb-case-gallery-row" data-id="' + Number(item.id) + '">' +
      '<span class="drb-case-gallery-handle" aria-hidden="true">⋮⋮</span><img src="' + source + '" alt="">' +
      '<input type="text" class="drb-case-gallery-label" placeholder="نام نما؛ مثال: نمای روبه‌رو" aria-label="نام نما">' +
      '<input type="text" class="drb-case-gallery-interval" placeholder="فاصله زمانی؛ در صورت تفاوت" aria-label="فاصله زمانی تصویر">' +
      '<div class="drb-case-gallery-actions"><button type="button" class="button-link drb-case-gallery-up" aria-label="انتقال به بالا">↑</button><button type="button" class="button-link drb-case-gallery-down" aria-label="انتقال به پایین">↓</button><button type="button" class="button-link-delete drb-case-gallery-remove">حذف</button></div></div>').find('.drb-case-gallery-label').val(label).end();
  }

  $(document).on('click', '.drb-select-case-gallery', function (event) {
    event.preventDefault();
    var editor = $(this).closest('.drb-case-gallery-editor');
    var frame = wp.media({ title: 'انتخاب نماهای این مراجعه', button: { text: 'افزودن به گالری بیمار' }, multiple: true, library: { type: 'image' } });
    frame.on('select', function () {
      var existing = {};
      editor.find('.drb-case-gallery-row').each(function () { existing[$(this).attr('data-id')] = true; });
      frame.state().get('selection').each(function (model) {
        var item = model.toJSON();
        if (!existing[item.id]) editor.find('.drb-case-gallery-rows').append(galleryRow(item));
      });
      syncGallery(editor);
    });
    frame.open();
  });

  $(document).on('input change', '.drb-case-gallery-label,.drb-case-gallery-interval', function () { syncGallery($(this).closest('.drb-case-gallery-editor')); });
  $(document).on('click', '.drb-case-gallery-remove', function () { var editor = $(this).closest('.drb-case-gallery-editor'); $(this).closest('.drb-case-gallery-row').remove(); syncGallery(editor); });
  $(document).on('click', '.drb-case-gallery-up,.drb-case-gallery-down', function () {
    var row = $(this).closest('.drb-case-gallery-row');
    if ($(this).hasClass('drb-case-gallery-up')) row.prev('.drb-case-gallery-row').before(row);
    else row.next('.drb-case-gallery-row').after(row);
    syncGallery(row.closest('.drb-case-gallery-editor'));
  });
});
