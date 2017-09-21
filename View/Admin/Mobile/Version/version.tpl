<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>版本管理</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"><!-- 确保适当的绘制和触屏缩放 -->
    <link rel="stylesheet" href="{$url_css}bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="{$url_css}admin.css" type="text/css">
    <script type="text/javascript" src="{$url_js}jquery-1.12.0.min.js"></script>
</head>
<body>

<div class="container" style="width:96%">
    <table class="table table-bordered table-striped" align="center">
        <tbody>
            <tr><td colspan="2"><h5>添加手机应用版本</h5></td></tr>
            <tr>
                <td class="col-xs-2">选择应用：</td>
                <td>
                    <select id="app_id" class="form-control" style="width: 300px;">
                    <option value="0">请选择需要更新的应用</option>
                    {foreach from=$app_ids key=key item=item}
                    <option value="{$key}">{$item.AppName|html}</option>
                    {/foreach}
                </select>
                </td>
            </tr>
            <tr>
                <td>应用版本号：</td>
                <td><input type="text" class="form-control" id="version_code" value="" placeholder="应用版本号" style="width: 300px;" /></td>
            </tr>
            <tr>
                <td>应用版本升级描述：</td>
                <td><textarea rows="5" cols="20" class="form-control" id="intro" style="width: 500px;" placeholder="应用版本升级描述"></textarea></td>
            </tr>
            <tr>
                <td>应用升级下载链接地址：</td>
                <td><input type="text" class="form-control" id="down_url" value="" placeholder="应用升级下载链接地址" style="width: 500px;" /></td>
            </tr>
            <tr>
                <td>&nbsp;</td>
                <td><input type="button" id="add_btn" class="btn btn-primary" value="添加" /></td>
            </tr>
        </tbody>
    </table>
</div>

<div class="container" style="width:96%">
    <div class="iframe-nav col-md-12" style="padding: 0">
        <ul class="nav nav-tabs" style="padding: 0 40px">
            {foreach $app_ids as $key => $item}
                <li class="iframe-title {if $app_id == $key}active{/if}">
                    <a href="?app_id={$key}" style="padding-right: 15px !important;">{$item.AppName|html}</a>
                </li>
            {/foreach}
        </ul>
    </div>

    <table class="table table-bordered table-striped table-hover" align="center">
        <thead>
        <tr>
            <td width="5%">ID</td>
            <td width="5%">版本号</td>
            <td>版本更新说明</td>
            <td width="18%">下载链接</td>
            <td width="160px">添加时间</td>
            <td width="70px">操作</td>
        </tr>
        </thead>
        <tbody>
            {if !empty($data)}
                {foreach from=$data item=item}
                    <tr>
                        <td>{$item.Id}</td>
                        <td>{$item.VersionCode|html}</td>
                        <td>{$item.Intro}</td>
                        <td>{$item.DownUrl|html}</td>
                        <td>{$item.CreateTime|html}</td>
                        <td><a class="btn btn-danger delete delete_btn" href="?do=delete&id={$item.Id}&app_id={$app_id}" _id="{$item.Id}">删除</a></td>
                    </tr>
                {/foreach}
            {/if}
            {if $pagination != ''}
                <tr><td colspan="6">{$pagination}</td></tr>
            {/if}
        </tbody>
    </table>
</div>

{literal}
<script>
$("#add_btn").click(function (){
    var app_id = parseInt($("#app_id").val()),
        version_code = $.trim($("#version_code").val()),
        intro = $.trim($("#intro").val()),
        down_url = $.trim($("#down_url").val());

    if (!app_id){
        top.swal('出错了', '请选择应用！', 'error');
        return false;
    }
    if (!version_code.length){
        top.swal('出错了', '应用版本号不能为空！', 'error');
        return false;
    }
    if (!intro.length){
        top.swal('出错了', '应用版本升级描述不能为空！', 'error');
        return false;
    }
    if (!down_url.length){
        top.swal('出错了', '应用升级下载链接地址不能为空！', 'error');
        return false;
    }

    $.post('?do=add', {'app_id' : app_id, 'version_code' : version_code, 'intro' : intro,  'down_url' : down_url}, function (data){
        if (typeof data == 'string')
            data = eval("("+data+")");

        if (data.status){
            top.swal('温馨提示', data.msg, 'success');
            self.location.reload();
        }else{
            top.swal('出错了', data.msg, 'error');
        }
    });
});

//删除
$(".delete_btn").click(function(){
    var url = self.location.pathname + $(this).attr("href");
    top.__confirm('确定要删除吗？','删除后不可恢复！', url, self);
    return false;
});
</script>
{/literal}
</body>
<html>