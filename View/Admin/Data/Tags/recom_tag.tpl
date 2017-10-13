<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>标签推荐</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"><!-- 确保适当的绘制和触屏缩放 -->
    <link rel="stylesheet" href="{$url_css}bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="{$url_css}admin.css" type="text/css">
    <link rel="stylesheet" href="{$url_css}tagcate.css" type="text/css">
    <script type="text/javascript" src="{$url_js}jquery-1.12.0.min.js"></script>
    <script type="text/javascript" src="{$url_js}tagcate.js"></script>
</head>
<body>

<div class="container" style="width:96%">
    <table class="table table-bordered table-striped" align="center">
        <tr>
            <td>
                <nav class="navbar navbar-default">
                    <div class="navbar-header">
                        <span class="navbar-brand">app标签推荐类别</span>
                    </div>
                    <div class="navbar-collapse">
                        <ul class="nav navbar-nav navbar-left">
                            {foreach $cate as $k=>$v}
                                {if $id==$k}
                                    <li class="active">
                                        <a href="?do=RecomTag&id={$k}">{$v['name']}</a>
                                    </li>
                                {else}
                                    <li>
                                        <a href="?do=RecomTag&id={$k}">{$v['name']}</a>
                                    </li>
                                {/if}
                            {/foreach}
                        </ul>
                    </div>
                </nav>
            </td>
        </tr>
    </table>
    <form action="?do=RecomTag" method="post" enctype="multipart/form-data">
        <input type="hidden" id="id" name="id" value="{$id}"/>
        <table class="table table-bordered table-striped" align="center">
            <tbody>
            <tr><td colspan="3"><h5>首页标签推荐</h5></td></tr>
            <tr>
                <td>
                    推荐标签：<br/>
                    填写自定义标签用空格分隔
                </td>
                <td>
                    <a class="btn btn-default" id="pop_tag_btn">选择标签</a><br/><br/>
                    <textarea class="form-control" rows="" cols="" id="tag" name="tag">{$tag}</textarea>
                </td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td >
                    <input type="submit" id="add_btn" class="btn btn-primary" value="保存" />
                    <input type="button" onclick="history.back()" class="btn btn-primary" value="取消" />
                </td>
            </tr>
            </tbody>
        </table>
    </form>
</div>
<div>&nbsp;</div>
<div class="modal"  id="pop_tag" style="display: none">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-job="close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title">标签选择器</h4>
            </div>
            <div class="modal-body" id="tag_content">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-job="close" >保存</button>
            </div>
        </div>
    </div>
</div>
{literal}
    <script type="text/javascript">
        (function() {
            var pop_tag = $('#pop_tag'),
                    getArrTag = function() {
                        var tag = $.trim($('#tag').val()),
                                tag_uniq = [];
                        if (tag.length > 0) {
                            var tag_arr = tag.split(' '),
                                    tag_obj = {};
                            for(var i = 0, len = tag_arr.length; i < len; i++)
                            {
                                tag_obj[tag_arr[i]] = tag_arr[i];
                            }
                            for(var x in tag_obj)
                            {
                                tag_uniq.push(tag_obj[x]);
                            }
                        }
                        return tag_uniq;
                    };

            var tc =  hd.tools.tagcate({
                'born' : function($w) {
                    $('#tag_content').append($w);
                    pop_tag.find("[data-job=close]").on('click', function() {
                        pop_tag.hide();
                    });
                    this.select(getArrTag());
                },
                'onSelect' : function(cates) {
                    var old_tag = getArrTag(),
                            name = cates[0];
                    if ($.inArray(name, old_tag) == -1) {
                        old_tag.push(name);
                        $('#tag').val(old_tag.join(' '));
                    }

                },
                'onCancel' : function(cates) {
                    var old_tag = getArrTag(),
                            name = cates[0],
                            pos = $.inArray(name, old_tag);
                    if (pos != -1) {
                        delete old_tag[pos];
                        $('#tag').val(old_tag.join(' '));
                    }
                }
            });

            $('#pop_tag_btn').on('click', function() {
                tc.clear();
                tc.select(getArrTag());
                pop_tag.show()
            });
        })();
    </script>
{/literal}

</body>
<html>