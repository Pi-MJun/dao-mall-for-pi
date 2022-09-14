// 楼层聚合数据高度处理
function FloorResizeHandle()
{
    $('.floor').each(function(k, v)
    {
        var height = $(this).find('.goods-list').height();
        $(this).find('.aggregation').css('height', ((window.innerWidth || $(window).width()) <= 640) ? 'auto' : height+'px');
    });
}

$(window).load(function()
{
    FloorResizeHandle();
});

$(function()
{
    // 新闻轮播
    if((window.innerWidth || $(window).width()) <= 640)
    {
        function AutoScroll()
        {
            $('.banner-news').find("ul").animate({
                marginTop: "-39px"
            }, 500, function() {
                $(this).css({
                    marginTop: "0px"
                }).find("li:first").appendTo(this);
            });
        }
        setInterval(function()
        {
            AutoScroll();
        }, 3000);
    }

    // 浏览器窗口实时事件
    $(window).resize(function()
    {
        FloorResizeHandle();
    });
});


$(function()
{
    console.log("Pi.init begin");

    console.log(window)

    console.log(navigator.userAgent)

    console.log(window.location.origin);

    if (window.location.origin == 'https://localhost:4430') {
      console.log("Pi.init with sandbox to true")
      Pi.init({ version: "2.0", sandbox: true});
    }else{
      console.log("Pi.init with sandbox to false")
      Pi.init({ version: "2.0"});
    }

    //Pi.init({ version: "2.0"});


    console.log("Pi.init end");
    console.log(Pi);
    console.log(Pi.authenticate);

});

