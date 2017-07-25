// 验证码
$('#code_img').on('click touchstart', function () {
    var url = $(this).attr('data-url');
    $(this).attr('src', url + '?t=' + Math.random());
});

var pkey = '-----BEGIN PUBLIC KEY-----MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAy61XYxT+z1bFSBoq+mG2Hibu0SQKfPLuzn7vD4qWBWMUKxPgBbbEMxMQcC+OAcsEA11Ipd6X3SvtFmPBiVB70Sftao1S/wjzvYIEli8JmuzU7liPuEr8+zbKQ2knrf59qipOH4S3aELl2Ef4SLbU7FtDRsQGNn/T+inljir9cPJRaAAGoLBfDDllS0SFOESqsH7kR7wtunnqr9QhMhUlP+IgFEPeXS3RztE4SMIYVMu99iH6CLZf0Cj7SsFr2/KLNI/NHXemk0sDlD2lrrZhvlaJtJN+w905J+3eUESDjfJT0srsT42rGCQwWclniME+qk2VIlnGTQGVpNLRpVZyBwIDAQAB-----END PUBLIC KEY-----';
rsa = new JSEncrypt();
rsa.setPublicKey(pkey);
$('#dologin,#doreg').click(function () {
    var username = $.trim($("#username").val());
    var pwd = $.trim($("#password").val());
    var code = $.trim($("#code").val());
    if (username == '' || pwd == '' || code == '') {
        sweetAlert('请输入用户名和密码及验证码');
    } else {
        $.ajax({
            url: "",
            type: "post",
            dataType: "json",
            data: {username: username, password: rsa.encrypt(pwd), code: code},
            success: function (re) {
                if (re.code == '200') {
                    top.location.href = re.data.url;
                } else {
                    sweetAlert(re.msg);
                    $('#code_img').click();
                    $("#code").val('')
                }
            }
        })
    }
    return false;
});
