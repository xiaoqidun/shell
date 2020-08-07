# 简而言之
大概是PHP最强大的SHELL执行类了
# 系统兼容
PHP 5.4 + (主要是用了中括号数组，改掉中括号数组可以要求更低)
# 使用说明
## 示范代码
```php
<?php
require 'shell.class.php';
$command = "ls /";
echo shell::command($command, "echo pwd", true);
?>
```
## example.php
这是一个使用shell.class.php实现的webshell工具，用于shell类演示。
# 授权说明
使用本类库你唯一需要做的就是把LICENSE文件往你用到的项目中拷贝一份。