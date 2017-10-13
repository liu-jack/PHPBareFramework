<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>评论管理</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"><!-- 确保适当的绘制和触屏缩放 -->
    <link rel="stylesheet" href="{$url_css}/bootstrap.min.css" type="text/css">
    <script type="text/javascript" src="{$url_js}jquery-1.12.0.min.js"></script>
    <script type="text/javascript" src="{$url_js}calendar/WdatePicker.js"></script>
</head>
<body>
<div class="container" style="width:96%">
    <form action="?do=default" method="GET">
        <table class="table table-bordered table-striped" align="center">
            <tr>
                <td colspan="8"><b>查询条件</b></td>
            </tr>
            <tr>
                <td class="form-group col-xs-2" colspan="1">
                    <div class="input-group">
                        <div class="input-group-addon">评论用户的ID</div>
                        <input type="text" class="form-control" name="userid" placeholder="评论用户的ID" value="{if $searchdata.UserId}{$searchdata.UserId}{/if}">
                    </div>
                </td>
                <td class="form-group col-xs-2" colspan="">
                    <div class="input-group">
                        <div class="input-group-addon">评论对象的ID</div>
                        <input type="text" class="form-control" name="itemid" placeholder="评论对象的ID" value="{if $searchdata.ItemId}{$searchdata.ItemId}{/if}">
                    </div>
                </td>
                <td class="form-group col-xs-2">
                    <div class="input-group" style="width: 220px">
                        <div class="input-group-addon">评论类型</div>
                        <select class="form-control type"  name="type">
                            <option value="-100">请选择</option>
                            {foreach $type as $k => $v}
                                <option value="{$k}" {if $searchdata.Type == $k}selected="selected"{/if}>{$v}</option>
                            {/foreach}
                        </select>
                    </div>
                </td>
                <td class="form-group col-xs-2">
                    <div class="input-group">
                        <div class="input-group-addon">状态</div>
                        <select class="form-control status"  name="status">
                            {foreach $status as $k => $v}
                                <option value="{$k}" {if $searchdata.Status == $k}selected="selected"{/if} {$status_color[$k]}>{$v}</option>
                            {/foreach}
                        </select>
                    </div>
                </td>
                <td class="form-group col-xs-2">
                    <div class="input-group">
                        <div class="input-group-addon">评论</div>
                        <select class="form-control" name="is_good">
                            <option value="">请选择</option>
                            <option value="1" {if $searchdata.IsGood == 1}selected{/if}>一般评论</option>
                            <option value="100" {if $searchdata.IsGood == 100}selected{/if}>优质评论</option>
                        </select>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="form-inline col-xs-2">
                    <div class="input-group">
                        <div class="input-group-addon">开始时间</div>
                        <input class="form-control" id="start_time" name="start_time" type="text" onFocus="WdatePicker({literal}{startDate:'%y-%M-%d 00:00:00',dateFmt:'yyyy-MM-dd HH:mm:ss',maxDate:'#F{$dp.$D(\'end_time\')}'}{/literal});" value="{if $searchdata.start_time}{$searchdata.start_time}{/if}" readonly="">
                    </div>
                </td>
                <td class="form-inline col-xs-2">
                    <div class="input-group">
                        <div class="input-group-addon">结束时间</div>
                        <input class="form-control" id="end_time" name="end_time" type="text" onFocus="WdatePicker({literal}{startDate:'%y-%M-%d 23:59:59',dateFmt:'yyyy-MM-dd HH:mm:ss',minDate:'#F{$dp.$D(\'start_time\')}'}{/literal});" value="{if $searchdata.end_time}{$searchdata.end_time}{/if}" readonly="">
                    </div>
                </td>
                <td class="form-group col-xs-2" colspan="3" align="right">
                    <input type="submit" value="搜索" class="btn btn-primary" />
                </td>
            </tr>
        </table>
    </form>

    <table class="table table-bordered table-striped table-hover table-formdata" align="center">
        <tbody>
        <tr align="center">
            {if $searchdata.Status > 0}
                <td>&nbsp;</td>
            {/if}
            <td>用户ID</td>
            <td>评论对象的ID</td>
            <td>回复评论的ID</td>
            <td>@用户的ID</td>
            <td>评论类型</td>
            <td>评论内容</td>
            <td>来源平台</td>
            <td>当前状态</td>
            <td>评论状态</td>
            <td>子评论量</td>
            <td>评论时间</td>
            <td>操作</td>
        </tr>

        {if !empty($data)}
            {foreach $data as $v}
                <tr align="center">
                    {if $v.Status > 0}
                        <td>
                            <label><input type="checkbox" class="checkall" name="delid[]" value="{$v.CommentId}" /></label>
                        </td>
                    {/if}
                    <td id="user_id">{$v.UserId}</td>
                    <td id="item_id">{$v.ItemId}</td>
                    <td>{$v.ReplyId}</td>
                    <td>{$v.AtUserId}</td>
                    <td>{$type[$v.Type]}</td>
                    <td class="col-xs-3"><span title="{$v.Content|html}">{$v.Content|truncate:20:"...":true|html}</span></td>
                    <td>{$platform[$v.Platform]}</td>
                    <td {$status_color[$v.Status]}>{$status[$v.Status]}</td>
                    <td id="is{$v.CommentId}" {if $v.IsGood == '一般'}style="color: #9B410E" {else} style="color: #19A6FF" {/if}>{$v.IsGood}</td>
                    <td>{$v.SubCommentCnt}</td>
                    <td>{$v.CreateTime}</td>
                    <td>
                        <button type="submit" id="iq{$v.CommentId}" {if $v.Status == 0 || $v.IsGood == '优质'} style="display: none" {/if} class="btn btn-success" onclick="quality({$v.CommentId}, 1)">优质评论</button>
                        <button type="submit" id="io{$v.CommentId}" {if $v.Status == 0 || $v.IsGood == '一般'} style="display: none" {/if} class="btn btn-info" onclick="quality({$v.CommentId}, 2)">取消优质</button>
                        {if $v.Status == 0}不可操作{else}<button type="submit" class="btn btn-default delete" mid="{$v.CommentId}">删除</button>{/if}
                    </td>
                </tr>
            {/foreach}
            <tr>
                <td colspan="13">
                    <div class="checkbox" style="height:20px">
                        <label><input id="checkbox" type="checkbox"/>全选</label>
                        <button type="submit" class="btn btn-default" id="getValue">删除</button>
                    </div>
                </td>
            </tr>
        {else}
            <tr align="center">
                <td colspan="12">本页暂无相关数据</td>
            </tr>
        {/if}
        </tbody>
    </table>
    {if $pagination != ''}
        <div colspan="6" align="right">{$pagination}</div>
    {/if}
</div>

<script type="text/javascript">
    {literal}
    $("#checkbox").click(function() {
        if ($(this).prop('checked')) {
            $('.checkall').prop('checked', true);
        } else {
            $('.checkall').prop('checked', false);
        }
    });

    $('#getValue').click(function () {
        top.swal({
            title: '确定要删除吗？',
            text: '删除后不可恢复！',
            type: "warning",
            showCancelButton: true,
            cancelButtonText:'取消',
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "确定",
            closeOnConfirm: false
        }, function() {
            var id = '';
            $("input[name='delid[]']").each(function() {
                if ($(this).is(':checked')) {
                    id+=id != ''?',':'';
                    id+=$(this).val();
                }
            });
        if (id) {
            $.ajax({
                url: 'manage.php?do=Delete',
                type: 'post',
                data: {'id': id},
                dataType: 'json',
                success: function (re) {
                    top.swal(re,function(){
                        self.location.reload();
                        });
                    }
                });
            }
        })
    });

    $(".delete").click(function(){
        var id = $(this).attr("mid");
        top.swal({
            title: '确定要删除吗？',
            text: '删除后不可恢复！',
            type: "warning",
            showCancelButton: true,
            cancelButtonText:'取消',
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "确定",
            closeOnConfirm: false
        }, function() {
            if (id) {
                $.ajax({
                    url: 'manage.php?do=Delete',
                    type: 'post',
                    data: {'id': id},
                    dataType: 'json',
                    success: function (re) {
                        top.swal(re, function(){
                            self.location.reload();
                        });
                    }
                });
            }
        });
    });

    //优质评论
    function quality(comment_id, num) {
        var item_id = $("#item_id").html();
        var user_id = $("#user_id").html();
        if (num == 1) {
            top.swal({
                title: '确定要设为优质评论？',
                text:'设置后将增加金币',
                type: "warning",
                showCancelButton: true,
                cancelButtonText:'取消',
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "确定",
                closeOnConfirm: false
            }, function() {
                $.ajax({
                    url: 'manage.php?do=quality',
                    type: 'post',
                    data: {'comment_id': comment_id, 'item_id': item_id, 'user_id':user_id},
                    dataType: 'json',
                    success: function (re) {
                        if (re.type == 'success') {
                            $("#is" + comment_id).html('优质').css('color', '#19A6FF');
                            $("#iq" + comment_id).hide();
                            $("#io" + comment_id).show();
                            top.swal("奖励成功!", "奖励了: 100金币", "success");
                        } else {
                            top.swal("奖励失败!");
                        }
                    }
                });
            });
        } else {
            top.swal({
                title: '确定要取消优质评论？',
                text:'取消后将扣除金币',
                type: "warning",
                showCancelButton: true,
                cancelButtonText:'取消',
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "确定",
                closeOnConfirm: false
            }, function() {
                $.ajax({
                    url:'manage.php?do=ordinary',
                    type:'post',
                    data:{'item_id':item_id,'comment_id':comment_id, 'user_id':user_id},
                    dataType:'json',
                    success: function (re) {
                        if (re.type == 'success') {
                            $("#is" + comment_id).html('一般').css('color', '#9B410E');
                            $("#io" + comment_id).hide();
                            $("#iq" + comment_id).show();
                            top.swal("取消成功！", "success");
                        } else {
                            top.swal("取消失败！", "error");
                        }
                    }
                });
            });
        }
    }

    {/literal}
</script>
</body>
</html>
