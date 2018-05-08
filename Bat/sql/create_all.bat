cd ../../

REM create database
start cmd /c "mode con cols=45 lines=10 && title book && php index.php Tool/Sql/createdb

REM book
start cmd /c "mode con cols=45 lines=10 && title book && php index.php Tool/Sql/book"
start cmd /c "mode con cols=45 lines=10 && title column && php index.php Tool/Sql/column"
start cmd /c "mode con cols=45 lines=10 && title content && php index.php Tool/Sql/content"

REM 用户
start cmd /c "mode con cols=45 lines=10 && title passport && php index.php Tool/Sql/passport"
start cmd /c "mode con cols=45 lines=10 && title account && php index.php Tool/Sql/account"

REM 共用
start cmd /c "mode con cols=45 lines=10 && title favorite && php index.php Tool/Sql/favorite"
start cmd /c "mode con cols=45 lines=10 && title comment && php index.php Tool/Sql/comment"
start cmd /c "mode con cols=45 lines=10 && title tag && php index.php Tool/Sql/tag"

REM app
start cmd /c "mode con cols=45 lines=10 && title application && php index.php Tool/Sql/application"
start cmd /c "mode con cols=45 lines=10 && title mobile && php index.php Tool/Sql/mobile"

REM 后台
start cmd /c "mode con cols=45 lines=10 && title admin && php index.php Tool/Sql/admin"

REM 采集
start cmd /c "mode con cols=45 lines=10 && title collect && php index.php Tool/Sql/collect"
start cmd /c "mode con cols=45 lines=10 && title picture && php index.php Tool/Sql/picture"

REM 支付平台
start cmd /c "mode con cols=45 lines=10 && title collect && php index.php Tool/Sql/payment"