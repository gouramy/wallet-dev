(($)=>{
    $(document).on('click','.wallet-btn[data-js-action]',function (e){
        e.preventDefault();
        $(window).trigger($(this).data('js-action'),$(this).data('js-data'));
    });
})(jQuery)