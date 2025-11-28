#命令使用说明
##创建模块
    php artisan module moduleName
moduleName：模块名

##创建模型文件
    php artisan make:tpl moduleName ActionName
moduleName：模块名

actionName：文件名称

例：php artisan make:tpl User CompanyUser

以上命令会再User目录下的Facades、Services、Models新建CompanyUser对应的类，已经存在的文件不会再创建，但provider需要手动处理

##生成API文档
    php artisan make:doc
##清除缓存
    php artisan cache key
key：key=flush 时清除所有缓存 其他的是清除带有key字符的缓存

## 成客户端 模块
    php artisan make:api {ActionName}
    
## 生成文章的pageinfo记录
    php artisan make:setpageinfo {module}