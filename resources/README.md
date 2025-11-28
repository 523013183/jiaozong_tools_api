## 目录结构

```
public.assets.css    	  html样式文件目录
  ├─layouts               公共样式文件目录
  ├─ ...                  更多目录
public.assets.js     	  html脚本文件目录
  ├─layouts               公共脚本文件目录
  ├─ ...                  更多目录
resources.lang            语言包文件目录
resources.views           html视图文件目录
  ├─components            组件视图目录
  │  ├─index              首页组件(可更改)
  │  │  ├─banner.blade.php广告位组件(可更改)
  │  │  └─ ...            更多组件文件
  │  ├─ ...               更多目录
  ├─layouts               公共布局视图目录
  │  ├─app.blade.php      共有视图
  │  ├─page.blade.php     分页视图
  |  ├─ ...               更多视图
  ├─index                 首页视图目录（可定义）
  │  ├─index.blade.php    首页视图
  │  ├─ ...               更多视图
  ├─news                  资讯视图目录（可定义）
  │  ├─index.blade.php    资讯列表视图
  │  ├─ ...               更多视图
  ├─live                  直播视图目录（可定义）
  │  ├─index.blade.php    直播列表视图
  │  ├─ ...               更多视图
  ├─expo                  展会视图目录（可定义）
  │  ├─index.blade.php    展会列表视图
  │  ├─ ...               更多视图
  ├─homepage              主页视图目录（可定义）
  │  ├─detail.blade.php   主页视图
  ├─expert                专家视图目录（可定义）
  │  ├─detail.blade.php   专家视图
  ├─ ...                  更多视图目录（可定义）
```

## 开发规范

#### 视图规范

1. 对应模块的视图需放在对应模块目录下

   ```
   路由地址：/{module}-{mainpage}/{plink}.html
   	  module(模块) ，如： index,news,live 等
   	  mainpage(主页id)
   	  plink(详情对应的生成的key,同主页同模块不可重复)
   	  
   如果请求地址为/index.html ，则视图文件路径：/resources/views/index/index.blade.php
   如果请求地址为/news.html ，则视图文件路径：/resources/views/news/index.blade.php
   如果请求地址为/news-2/pg8282.html, 则视图文件路径：/resources/views/news/detail.blade.php
   ```

2. 所有视图需继承/view/layouts/app.blade.php文件

   ```
   参考： /resources/view/index/index.blade.php
   
   @section('content')
   	 当前视图主体内容
   @endsection
   ```

4. js、css引用: 

   ```html
   {{-- 推送js文件到公共布局中 --}}
   @push('scripts')
       <script src="/assets/js/example.js" type="javascript"></script>
   @endpush
   {{-- 推送css文件到公共布局中 --}}
   @push('css')
       <link href="/assets/css/example.css" rel="stylesheet" type="text/css"/>
   @endpush
   ```

   

5. 其他写法： https://learnku.com/docs/laravel/8.x/blade/9377#918cb6
6. 视图兼容多语言使用： {{ transL('common.param_format_error', '发生错误了') }} 来输出文本

#### 组件规范

1. 调用方式

   ```html
   <x-blog-list view-file="components.blog.list" :page="$page ?? 1" :page-size="$pageSize" industry-id="43" is-tourist="0"  />
   <!--
   1. 调用组件必须<x-组件名称 />
   2. 传递参数时使用 烤串式 类型：page-size
      变量应该通过以 : 字符作为前缀的变量来进行传递
   3. 必传参数 view-file (组件调用的视图文件)
   4. 固定参数 :page, :page-size, 如果不确定参数是否存在 使用 "$page ?? 1" 设置默认的值 否则报错
   -->
   ```

2. 对应的组件名称，参数等都会已文档形式提供

