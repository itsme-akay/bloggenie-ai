(function($){
  'use strict';

  var $wrap  = $('#bgai-kw-wrap');
  var $input = $('#bgai-kw-input');
  var $raw   = $('#bgai-kw-raw');

  if ( ! $wrap.length ) return;

  $wrap.on('click', function(){ $input.focus(); });

  $input.on('keydown', function(e){
    if ( e.key !== 'Enter' && e.key !== ',' ) return;
    e.preventDefault();
    var val = $(this).val().trim().replace(/,+$/, '');
    if ( val.length < 2 ) return;
    addTag(val);
    $(this).val('');
    syncRaw();
  });

  $(document).on('click', '.bgai-kw-x', function(){
    $(this).closest('.bgai-kw-tag').remove();
    syncRaw();
  });

  function addTag(val){
    var existing = [];
    $('.bgai-kw-tag').each(function(){
      existing.push( $(this).text().replace('×','').trim().toLowerCase() );
    });
    if ( existing.indexOf(val.toLowerCase()) !== -1 ) return;
    var $tag = $('<span class="bgai-kw-tag"></span>')
      .text(val)
      .append(' <span class="bgai-kw-x">&#215;</span>');
    $input.before($tag);
  }

  function syncRaw(){
    var kws = [];
    $('.bgai-kw-tag').each(function(){
      kws.push( $(this).text().replace('×','').trim() );
    });
    $raw.val( kws.join(', ') );
  }

})(jQuery);
