$(document).ready(function () {
// 回到顶部
    $("#gotop").on("click touchstart",function(){
        $("html,body").animate({scrollTop: 0}, 500);
    });
    $("#gotop").on("mouseover touchstart",function(){
        $(this).css('background-position', '-90px -445px');
    });
    $("#gotop").on("mouseout touchend",function(){
        $(this).css('background-position', '-124px -445px');
    });
// 直达底部
    $("#gobottom").on("click",function(){
        $('html,body').animate({scrollTop:$('.footer').offset().top}, 500);
    });
// 推荐
    var isRecmBookPost = false;
    $(".recmBook").on("click",function(){
        var bid = $(this).attr('data-bid');
        if(bid){
            if (!isRecmBookPost) {
                isRecmBookPost = true;
                $.post('/book/index/recommend',{bid:bid},function(ret){
                    isRecmBookPost = false;
                    if(ret.code === 200 || ret.code === 201){
                        $(".recmBook").html('已推荐');
                    }
                    if (ret.msg) {
                        sweetAlert(ret.msg);
                    }
                },'json');
            }
        }
    });
// 收藏
    var isFavBookPost = false;
    $(".favBook").on("click",function(){
        var bid = $(this).attr('data-bid');
        if(bid){
            if (!isFavBookPost) {
                isFavBookPost = true;
                $.post('/book/index/favorite',{bid:bid},function(ret){
                    isFavBookPost = false;
                    if(ret.code === 200){
                        $(".favBook").html('取消收藏');
                    } else if (ret.code === 201) {
                        $(".favBook").html('收藏本书');
                    } else if (ret.code === 551) {
                        location.href = ret.data.url;
                        return false;
                    }
                    if (ret.msg) {
                        sweetAlert(ret.msg);
                    }
                },'json');
            }
        }
    });
});

// 列表
var offset = 0,isGetAjaxList = false;
function getAjaxList() {
    if (!isGetAjaxList){
        isGetAjaxList = true;
        offset += 10;
        $('#search_more_btn').html('数据加载中...');
        $.ajax({
            dataType: "json",
            data: {offset:offset},
            success: function(ret) {
                var data = ret.data;
                if(ret.code === 200 && data.list){
                    var html = '', list = data.list;
                    for(var i=0,j=list.length; i<j; i++){
                        html +='<li>';
                        html +='    <a href="/book/'+data.list[i].DefaultFromSite +'_'+list[i].BookId+'.html">';
                        html +='        <div class="bcover fl">';
                        html +='            <img src="'+list[i].Cover+'" alt="'+list[i].BookName+'" height="130" width="85" />';
                        html +='        </div>';
                        html +='        <div class="bintro pl10">';
                        html +='            <h4>'+list[i].BookName+'</h4> ';
                        html +='            <p>'+list[i].TypeName+' . '+list[i].Author;
                        html +='              <br>'+list[i].BookDesc+'</p>';
                        html +='        </div>';
                        html +='    </a>';
                        html +='</li>';
                    }
                    $('#search_list').append(html);
                    if(list.length === 10){
                        $('#search_more_btn').html('加载更多');
                        isGetAjaxList = false;
                    }else{
                        $('#search_more_btn').remove();
                    }
                }else{
                    isGetAjaxList = false;
                    setTimeout(function () {
                        $('#search_more_btn').hide();
                    }, 3000);
                    if(ret.code === 201){
                        $('#search_more_btn').html('没有更多数据了');
                    } else {
                        sweetAlert('网络繁忙，请稍后再试！');
                    }
                }
            },
            error: function () {
                isGetAjaxList = false;
            }
        });
    }
}
