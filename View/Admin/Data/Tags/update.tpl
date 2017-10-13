<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>添加标签</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"><!-- 确保适当的绘制和触屏缩放 -->
    <link rel="stylesheet" href="{$url_css}bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="{$url_css}admin.css" type="text/css">
    <link rel="stylesheet" href="{$url_js}fileinput/css/fileinput.min.css" type="text/css">
    <script type="text/javascript" src="{$url_js}jquery-1.12.0.min.js"></script>
    <script type="text/javascript" src="{$url_js}fileinput/js/fileinput.min.js"></script>
    <script type="text/javascript" src="{$url_js}fileinput/js/locales/zh.js"></script>
</head>
<body>

<div class="container" style="width:96%">
        <table id="myformtable" class="table table-bordered table-striped" align="center">
            <input type="hidden" id="id" name="id" value="{$info.TagId}" />
            <tbody>
            <tr><td colspan="3"><h5>添加标签</h5></td></tr>
            <tr>
                <td>标签名称：</td>
                <td><input type="text" class="form-control" id="tagname" name="tagname" value="{$info.TagName}" placeholder="标签名称" style="width: 300px;" {if $info}readonly{/if} /></td>
            </tr>
            <tr>
                <td>
                    标签ICON（圆角图片中处理）<br/>
                    仅限png格式<br/>
                    不超过2M<br/>
                </td>
                <td>
                    <input type="file" class="file_img" id="icon" name="icon"  minW="" minH="" maxSize="2048"  data="{$info['Icon']}" accept=".png" onchange="verifyImg($(this))" />
                </td>
            </tr>
            <tr>
                <td>
                    封面<br/>
                    仅限jpg,png格式<br/>
                    不超过2M<br/>
                    尺寸最小640*260<br/>
                </td>
                <td>
                    <input type="file" class="file_img" id="cover" name="cover" minW="640" minH="260" maxSize="2048"  data="{$info['Cover']}" accept=".jpg, .png" onchange="verifyImg($(this))" />
                </td>
            </tr>
            <tr>
                <td>
                    banner图<br/>
                    注：标题和图片必选,大小不超过2M<br/>
                    标题或图片为空将清除该banner图
                    <iframe name='upframe' id="upframe" style='display:none'></iframe>
                </td>
                <td width="80%">
                    {if !isset($bannerid)}添加标签时不能修改，请保存后进入编辑再修改{/if}
                    <table align="center" id="code_show" style="width:100%" class="table table-striped table-bordered">
                        {if !empty($info['Banner'])}
                            {foreach from=$info['Banner'] item=item key=key}
                                <tr id="data_{$key}">
                                    <td>
                                        <div class="input-group">
                                            <div class="input-group-addon">标题</div>
                                            <input class="form-control" type="text" name="title[]" value="{$item.Title}">
                                        </div>
                                        <div class="input-group">
                                            <div class="input-group-addon">URL地址</div>
                                            <input class="form-control" type="text" name="url[]" value="{$item.Url}">
                                        </div>
                                        <form action="?do=UploadBanner&id={$info.TagId}&ver={$info.VerId}" enctype="multipart/form-data" method="POST" target="upframe">
                                            <div class="input-group">
                                                <div class="input-group-addon">选择图片</div>
                                                <input class="form-control" type="file" name="img_{$key}" onchange="fileChange('{$key}')">
                                            </div>
                                            <input type="hidden" name="file_name" value="img_{$key}">
                                        </form>
                                        <input type="hidden" name="img[]" value="{$item.ImgUrl}" />
                                    </td>
                                    <td width="100px" id="new_img_{$key}"><img src="{$item.autoImgUrl}" width="80"></td>
                                    <td width="8%">
                                        <a class="btn btn-default" onclick="addCode('{$key}');" href="javascript://">+</a>
                                        <a class="btn btn-default" onclick="delCode('{$key}');" href="javascript://">-</a>
                                    </td>
                                </tr>
                            {/foreach}
                        {/if}
                    </table>
                </td>
            </tr>
            <tr>
                <td>简介：</td>
                <td>
                    <textarea rows="3" cols="2" class="form-control" id="TagDesc" name="TagDesc" placeholder="标签简介" style="width: 400px;">{$info['TagDesc']}</textarea>
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
</div>
<input type="hidden" id="bannerid"  value="{$bannerid}" />
<script type="text/javascript">
    var action_url = "?do=UploadBanner&id={$info.TagId}&ver={$info.VerId}";
</script>
{literal}
    <script type="text/javascript">
        $('#add_btn').click(function () {
            $('form').remove();
            $('#myformtable').wrap('<form action="?do=Update" id="myform" method="post" enctype="multipart/form-data"></form>');
            $('#myform').submit();
            return false;
        });
        /**
         * 添加banner
         */
        function addCode(index) {
            var time = new Date().getTime();
            var tpl =
                    '<tr id="data_' + time + '">'+
                    '<td>'+
                    '<div class="input-group">'+
                    '<div class="input-group-addon">标题</div>'+
                    '<input class="form-control" type="text" name="title[]" value="">'+
                    '</div>'+
                    '<div class="input-group">'+
                    '<div class="input-group-addon">URL地址</div>'+
                    '<input class="form-control" type="text" name="url[]" value="">'+
                    '</div>'+
                    '<form action="'+action_url+'&order='+ time +'" enctype="multipart/form-data" method="POST" target="upframe">'+
                    '<div class="input-group">'+
                    '<div class="input-group-addon">选择图片</div>'+
                    '<input class="form-control" type="file" name="img_' + time + '" onchange="fileChange(' + time + ')">'+
                    '</div>'+
                    '<input type="hidden" name="file_name" value="img_' + time + '">'+
                    '</form>'+
                    '<input type="hidden" name="img[]" value="" />'+
                    '</td>'+
                    '<td width="100px" id="new_img_' + time + '"></td>'+
                    '<td width="8%">'+
                    '<a class="btn btn-default" onclick="addCode(' + time + ');" href="javascript://">+</a>'+
                    '<a class="btn btn-default" onclick="delCode(' + time + ');" href="javascript://">-</a>'+
                    '</td>'+
                    '</tr>';

            var $node = $("#data_" + index);
            if ($node.length) {
                $node.after(tpl);
            } else {
                $("#code_show").html(tpl);
            }
        }
        if ($.trim($("#bannerid").val()) == '0') { // 添加默认模版
            addCode(0);
        }
        /**
         * 删除广告
         */
        function delCode(index){
            if ($("#code_show tr").length <= 1) {
                top.swal('出错了', '至少拥有一个Banner图', 'error');
                return false;
            }
            $("#data_" + index).remove();
        }
        /**
         * 监听图片内容变动
         */
        function fileChange(index){
            $("input[name=img_" + index + "]").parents('form').submit();
        }
        /**
         * 图片上传回调函数
         */
        function _uploadImg(data) {
            if (typeof data == 'string') {
                data = eval('(' + data + ')');
            }

            if (data.status != 200) {
                top.swal('出错了', data.msg, 'error');
                return false;
            } else {
                var data = data['data'],
                        index = data['file_name'].split("_")[1],
                        html = '<img src="' + data['auto_img_url'] + '" width="80">&nbsp;&nbsp;<span style="color: red"></span>';

                $("#new_img_" + index).html(html);
                $("input[name=img_" + index + "]").parents('form').siblings("input[name='img[]']").val(data['img_url']);
            }
        }

        //验证图片的宽和高
        function verifyImg(obj) {
            $('#div_btn').find('input').attr('disabled', 'disabled');
            var file = obj[0].files[0];

            if (file.size > 2097152) {
                obj.attr('info', 'size');
                $('#div_btn').find('input').removeAttr('disabled');
                return;
            }
            var img_url = window.URL.createObjectURL(file);
            var minW, minH;
            minW = obj.attr('minW');
            minH = obj.attr('minH');
            obj.attr('info', 'yes');
            var ImgObj = new Image();
            ImgObj.onload = function () {
                if (minW > ImgObj.width) {
                    obj.attr('info', 'no');
                }
                if (minH > ImgObj.height) {
                    obj.attr('info', 'no');
                }
                $('#div_btn').find('input').removeAttr('disabled');
            }
            ImgObj.src = img_url;
        }
        //图片上传初始化
        $(".file_img").each(function () {
            //var accept = [$(this).attr("accept")];
            var input_obj = {
                showCaption: false,
                showClose: false,
                uploadAsync: false,
                showRemove: false,
                showUpload: false,
                uploadUrl: "#",
                language: 'zh', //设置语言
                allowedPreviewTypes: ['image'],
                allowedFileTypes: ['image'],
                //allowedFileExtensions: accept,//接收的文件后缀
                dropZoneEnabled: false, //拖拽区域
            };
            //$(this).attr("accept", '.' + accept.join(',.'));

            var img_url = $(this).attr("data");
            if (img_url != '') {
                input_obj.initialPreview = [
                    '<img src="' + img_url + '" class="kv-preview-data file-preview-image" style="width:auto;height:160px;">'
                ];
            }
            var maxSize = $(this).attr("maxSize");
            if (maxSize > 0) {
                input_obj.maxFileSize = maxSize;
            }
            $(this).fileinput(input_obj);
        });

    </script>
{/literal}

</body>
<html>