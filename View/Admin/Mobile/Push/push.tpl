<!doctype html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <link rel="stylesheet" href="{$url_css}bootstrap.min.css" type="text/css">
    <script type="text/javascript" src="{$url_js}jquery-1.12.0.min.js"></script>
    <script type="text/javascript" src="{$url_js}bootstrap.min.js"></script>
    <script type="text/javascript" src="{$url_js}calendar/WdatePicker.js"></script>
    <title>推送消息管理</title>
</head>
<body>
<br/>
<div style="width:96%" class="container">

    <ul class="nav nav-tabs">
        <li class="active"><a href="#public" data-toggle="tab">公用消息推送</a></li>
        <li><a href="#tag" data-toggle="tab">按用户类型推送</a></li>
        <li><a href="#personal" data-toggle="tab">个人消息推送</a></li>
    </ul>
    <br/>

    <div class="tab-content">
        <div id="public" class="tab-pane active">
            <form method="post" id="public-form">
                <table class="table table-striped table-bordered" align="center">
                    <tr>
                        <th colspan="2">推送消息管理</th>
                    </tr>
                    <tr>
                        <td width="15%">推送消息类型</td>
                        <td>
                            <select name="type" class="form-control" style="width: 200px">
                                <option value="0">==选择推送消息类型==</option>
                                {foreach $types as $v}
                                <option value="{$v['TypeId']}">{$v['TypeName']}</option>
                                {/foreach}
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>推送平台</td>
                        <td>
                            {foreach $system as $k => $v}
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="appid[]" value="{$v['AppId']}"
                                           checked="checked"/> {$v['AppName']}
                                </label>
                            {/foreach}
                        </td>
                    </tr>
                    <tr>
                        <td>推送消息</td>
                        <td><input class="form-control" type="text" class="span6" name="msg" style="width: 80%"/></td>
                    </tr>
                    <tr class="input_data">
                        <td>数据</td>
                        <td>
                            <input class="form-control" type="text" class="span6" name="data" style="width: 80%"/>
                            <br/>
                            [推送纯消息 -> 此字段留空]<br/>
                            [推送URL -> 填写URL]<br/>
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>
                            <input type="button" class="btn btn-danger" id="public-submit" value="提交"/>
                            请谨慎填写，这里的内容不可撤销，一旦提交就会影响数所有用户
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>
                            <input type="text" class="span1" name="settime" onFocus="WdatePicker({literal}{dateFmt:'yyyy-MM-dd HH:mm',minDate:'%y-%M-%d %H:#{%m+5}}'}{/literal});" value="" readonly/>
                            <input type="button" class="btn btn-danger" id="settime-push" value="定时推送"/>
                        </td>
                    </tr>
                </table>
            </form>
        </div>

        <div id="tag" class="tab-pane">
            <form method="post" id="tag-form">
                <table class="table table-striped table-bordered" align="center">
                    <tr>
                        <th colspan="2">推送消息管理</th>
                    </tr>
                    <tr>
                        <td width="15%">推送消息类型</td>
                        <td>
                            <select name="type" class="form-control" style="width: 200px">
                                <option value="0">==选择推送消息类型==</option>
	                            {foreach $types as $v}
                                    <option value="{$v['TypeId']}">{$v['TypeName']}</option>
	                            {/foreach}
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>推送平台</td>
                        <td>
                            {foreach $system as $k => $v}
                                <label class="checkbox-inline">
                                    <input type="checkbox" name="appid[]" value="{$v['AppId']}"
                                           checked="checked"/> {$v['AppName']}
                                </label>
                            {/foreach}
                        </td>
                    </tr>
                    <tr>
                        <td>用户类型</td>
                        <td>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="tag[]" value="STAGE0" /> 未登录
                            </label>
                            <label class="checkbox-inline">
                                <input type="checkbox" name="tag[]" value="STAGE10" /> 已登录
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <td>推送消息</td>
                        <td><input class="form-control" type="text" class="span6" name="msg" style="width: 80%"/></td>
                    </tr>
                    <tr class="input_data">
                        <td>数据</td>
                        <td>
                            <input class="form-control" type="text" class="span6" name="data" style="width: 80%"/>
                            <br/>
                            [推送纯消息 -> 此字段留空]<br/>
                            [推送URL -> 填写URL]<br/>
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>
                            <input type="button" class="btn btn-danger" id="tag-submit" value="提交"/>
                            请谨慎填写，这里的内容不可撤销，一旦提交就会影响数所选用户类型的所有用户
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>
                            <input type="text" class="span1" name="settime" onFocus="WdatePicker({literal}{dateFmt:'yyyy-MM-dd HH:mm',minDate:'%y-%M-%d %H:#{%m+5}}'}{/literal});" value="" readonly/>
                            <input type="button" class="btn btn-danger" id="settime-tag-push" value="定时推送"/>
                        </td>
                    </tr>
                </table>
            </form>
        </div>

        <div class="tab-pane fade" id="personal">
            <form method="post" id="personal-form">
                <table class="table table-striped table-bordered" align="center">
                    <tr>
                        <th colspan="2">推送消息管理</th>
                    </tr>
                    <tr>
                        <td>推送平台</td>
                        <td>
                            <select class="form-control" name="appid" class="input-medium" id="persional-where"
                                    style="width: 200px">
                                {foreach $system as $k => $v}
                                <option value="{$v['AppId']}">{$v['AppName']}</option>
                                {/foreach}
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td width="15%">推送消息类型</td>
                        <td>
                            <select class="form-control" name="type" style="width: 200px">
                                <option value="0">==选择推送消息类型==</option>
                                {foreach $types as $k => $v}
                                    <option value="{$v['TypeId']}">{$v['TypeName']}</option>
                                {/foreach}
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            用户识别标识
                        </td>
                        <td>
                            <input class="form-control" type="text" class="input-xlarge" name="token" style="width: 80%"
                                   placeholder="个推推送ID"/>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            用户ID<br/>
                            用户识别标识与用户ID二选一，都填写了时以用户识别标识为准
                        </td>
                        <td>
                            <input class="form-control" type="text" class="input-xlarge" name="uid" style="width: 80%"
                                   placeholder="个推用户ID"/>
                        </td>
                    </tr>
                    <tr>
                        <td>推送消息</td>
                        <td><input class="form-control" type="text" class="span6" name="msg" style="width: 80%"/></td>
                    </tr>
                    <tr class="input_data">
                        <td>数据</td>
                        <td>
                            <input class="form-control" type="text" class="span6" name="data" style="width: 80%"/>
                            <br/>
                            [推送纯消息 -> 此字段留空]<br/>
                            [推送URL -> 填写URL]<br/>
                        </td>
                    </tr>
                    <tr>
                        <td>&nbsp;</td>
                        <td>
                            <input type="button" class="btn btn-danger" id="personal-submit" value="提交"/>
                            请谨慎填写，一但推送不可取消！
                        </td>
                    </tr>
                </table>
            </form>
        </div>
    </div>

</div>

<script type="text/javascript">
    {literal}
    function show_down(main) {
        var cur = main.find("select[name=type]");
        cur.change(function () {
            var input_data = main.find('.input_data');
            if (cur.val() == 1) {
                input_data.find('input').val('');
                input_data.hide();
            } else {
                input_data.show();
            }
        });
    }
    show_down($("#public-form"));
    show_down($("#tag-form"));
    show_down($("#personal-form"));

    $("#public-submit").click(function () {
        var that = $(this),
                reset = function () {
                    that.prop('disabled', false);
                    that.val('提交');
                }
        if ($(this).prop('disabled')) {
            return true;
        }

        $(this).prop('disabled', true);
        $(this).val('推送中...');

        top.swal({
            title: '推送确认',
            text: '请谨慎填写，一旦提交不可撤销!',
            type: "warning",
            showCancelButton: true,
            cancelButtonText: '取消',
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "确定",
            closeOnConfirm: false
        }, function (isConfirm) {
            if (isConfirm) {
                $.ajax({
                    url: '?do=PushAll',
                    type: "POST",
                    data: $("#public-form").serialize(),
                    dataType: "json",
                    success: function (json) {
                        setTimeout(function () {
                            top.swal(json);
                        }, 100);
                        if (json.type == 'success') {
                            $("#public-form").get(0).reset();
                            top.swal('success', json.msg, 'success');
                        } else {
                            top.swal('error', json.msg, 'error');
                        }
                        reset();
                    }
                });
            } else {
                reset();
            }

        });
    });
    //定时推送
    $("#settime-push").click(function () {
        var that = $(this),
                reset = function () {
                    that.prop('disabled', false);
                    that.val('定时推送');
                }
        if ($(this).prop('disabled')) {
            return true;
        }

        $(this).prop('disabled', true);
        $(this).val('设置中...');

        top.swal({
            title: '确认定时推送？',
            text: '请谨慎填写!',
            type: "warning",
            showCancelButton: true,
            cancelButtonText: '取消',
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "确定",
            closeOnConfirm: false
        }, function (isConfirm) {
            if (isConfirm) {
                $.ajax({
                    url: '?do=PushSetTime',
                    type: "POST",
                    data: $("#public-form").serialize(),
                    dataType: "json",
                    success: function (json) {
                        setTimeout(function () {
                            top.swal(json);
                        }, 100);
                        if (json.type == 'success') {
                            $("#public-form").get(0).reset();
                            top.swal('success', json.msg, 'success');
                        } else {
                            top.swal('error', json.msg, 'error');
                        }
                        reset();
                    }
                });
            } else {
                reset();
            }

        });
    });

    $("#tag-submit").click(function () {
        var that = $(this),
                reset = function () {
                    that.prop('disabled', false);
                    that.val('提交');
                }
        if ($(this).prop('disabled')) {
            return true;
        }

        $(this).prop('disabled', true);
        $(this).val('推送中...');

        top.swal({
            title: '推送确认',
            text: '请谨慎填写，一旦提交不可撤销!',
            type: "warning",
            showCancelButton: true,
            cancelButtonText: '取消',
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "确定",
            closeOnConfirm: false
        }, function (isConfirm) {
            if (isConfirm) {
                $.ajax({
                    url: '?do=PushTag',
                    type: "POST",
                    data: $("#tag-form").serialize(),
                    dataType: "json",
                    success: function (json) {
                        setTimeout(function () {
                            top.swal(json);
                        }, 100);
                        if (json.type == 'success') {
                            $("#tag-form").get(0).reset();
                            top.swal('success', json.msg, 'success');
                        } else {
                            top.swal('error', json.msg, 'error');
                        }
                        reset();
                    }
                });
            } else {
                reset();
            }

        });
    });
    //定时推送
    $("#settime-tag-push").click(function () {
        var that = $(this),
                reset = function () {
                    that.prop('disabled', false);
                    that.val('定时推送');
                }
        if ($(this).prop('disabled')) {
            return true;
        }

        $(this).prop('disabled', true);
        $(this).val('设置中...');

        top.swal({
            title: '确认定时推送？',
            text: '请谨慎填写!',
            type: "warning",
            showCancelButton: true,
            cancelButtonText: '取消',
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "确定",
            closeOnConfirm: false
        }, function (isConfirm) {
            if (isConfirm) {
                $.ajax({
                    url: '?do=PushSetTime',
                    type: "POST",
                    data: $("#tag-form").serialize(),
                    dataType: "json",
                    success: function (json) {
                        setTimeout(function () {
                            top.swal(json);
                        }, 100);
                        if (json.type == 'success') {
                            $("#tag-form").get(0).reset();
                            top.swal('success', json.msg, 'success');
                        } else {
                            top.swal('error', json.msg, 'error');
                        }
                        reset();
                    }
                });
            } else {
                reset();
            }

        });
    });

    $("#personal-submit").click(function () {
        var that = $(this),
                reset = function () {
                    that.prop('disabled', false);
                    that.val('提交');
                }
        if ($(this).prop('disabled')) {
            return true;
        }

        $(this).prop('disabled', true);
        $(this).val('推送中...');

        top.swal({
            title: '推送确认',
            text: '请谨慎填写，一旦提交不可撤销!',
            type: "warning",
            showCancelButton: true,
            cancelButtonText: '取消',
            confirmButtonColor: "#DD6B55",
            confirmButtonText: "确定",
            closeOnConfirm: false
        }, function (isConfirm) {
            if (isConfirm) {
                $.ajax({
                    url: '?do=PushPerson',
                    type: "POST",
                    data: $("#personal-form").serialize(),
                    dataType: "json",
                    success: function (json) {
                        setTimeout(function () {
                            top.swal(json);
                        }, 100);
                        if (json.type == 'success') {
                            $("#personal-form").get(0).reset();
                            top.swal('success', json.msg, 'success');
                        } else {
                            top.swal('error', json.msg, 'error');
                        }
                        reset();
                    }
                });
            } else {
                reset();
            }
        });
    });
    {/literal}
</script>
</body>
</html>