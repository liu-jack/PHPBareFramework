<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns="http://www.w3.org/1999/html">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>故事标签</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"><!-- 确保适当的绘制和触屏缩放 -->
    <link rel="stylesheet" href="{$url_css}bootstrap.min.css" type="text/css">
    <link rel="stylesheet" href="{$url_css}admin.css" type="text/css">
    <script type="text/javascript" src="{$url_js}jquery-1.12.0.min.js"></script>
    <script type="text/javascript" src="{$url_js}calendar/WdatePicker.js"></script>
    <style type="text/css">
        {literal}
        .page_info div{display: inline-block;}
        .page_info .p_l{
            color: #666;
            float: left;
            line-height: 69px;
            font-size: 16px;
        }
        .page_info .p_r{
            color: #337AB7;
            float: right;
        }
        {/literal}
    </style>
</head>
<body>
<div class="container" style="width:96%">
    <div class="row">
        <div class="col-md-2"><a href="?do=add" class="btn btn-primary">添加</a></div>
    </div>
    <br/>
</div>

<div class="container" style="width:96%">
    <form action="" method="get">
        <table class="table table-bordered table-striped" align="center">
            <tr>
                <td colspan="6">查询条件</td>
            </tr>
            <tr>
                <td class="form-group col-xs-2">
                    <div class="input-group">
                        <div class="input-group-addon">标签ID</div>
                        <input type="text" class="form-control" name="tagid" placeholder="标签ID" value="{if $tagid}{$tagid}{/if}">
                    </div>
                </td>
                <td class="form-group col-xs-2">
                    <div class="input-group">
                        <div class="input-group-addon">标签名称</div>
                        <input type="text" class="form-control" name="tagname" placeholder="标签名称" value="{if $tagname}{$tagname}{/if}">
                    </div>
                </td>
                <td class="form-group col-xs-2" colspan="3">
                    <input type="submit" value="搜索" class="btn btn-primary" />
                </td>
            </tr>
        </table>
    </form>
    <table class="table table-bordered table-striped" align="center">
        <thead>
        <tr>
            <th>ID</th>
            <th>名称</th>
            <th>Icon</th>
            <th>封面</th>
            <th>操 作</th>
        </tr>
        </thead>
        <tbody>
            {if $list}
            {foreach $list as $v}
                <tr>
                    <td width="200px">{$v.TagNameId}</td>
                    <td>{$v.TagName|html}</td>
                    <td><img src="{$v.Icon}" alt="" height="50"/></td>
                    <td><img src="{$v.Cover}" alt="" height="50"/></td>
                    <td align="center">
                        <a href="?do=Edit&id={$v.TagNameId}" class="btn btn-default">修改</a>
                    </td>
                </tr>
            {/foreach}
            {/if}

            <tr>
                <td colspan="10" align="right">
                    <div class="page_info">
                        {if $page_info['count']}<div class="p_l">一共 {$page_info['count']} 条数据,共 {$page_info['page']} 页</div>{/if}
                        <div class="p_r">{$pagination}</div>
                    </div>
                </td>
            </tr>
        </tbody>
        </table>
</div>
{literal}

<script type="text/javascript">
    //删除
    $(".delete").click(function(){
        var url = self.location.pathname + $(this).attr("href");
        top.__confirm('确定要删除吗？','删除后不可恢复！', url, self);
        return false;
    });
</script>
{/literal}
</body>
</html>