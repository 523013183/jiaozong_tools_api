<html lang="zh">
<head>
    <meta charset="UTF-8" />
    <title>404</title>
    <link rel="shortcut icon" href="/favicon.ico" />
    <style>
        .undefined_contain {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 94vh;
            flex-wrap: wrap;
            flex-direction: column;
        }
        .image {
            height: 40vh;
        }
        .error_title {
            font-size: 18px;
            margin-top: 30px;
            width: 100%;
            font-family: '思源黑体旧字形 Regular';
            font-weight: bold;
            color: #53565c;
            line-height: 28px;
            text-align: center;
        }
        .error_desc {
            font-size: 14px;
            font-family: '思源黑体旧字形 Regular';
            font-weight: 400;
            color: #53565c;
            line-height: 22px;
        }
        .error_btn {
            cursor: pointer;
            margin-top: 40px;
            font-size: 12px;
            padding: 2px 16px;
            background: #ffffff;
            border-radius: 41px;
            border: 1px solid #3888ff;
            font-family: '思源黑体旧字形 Regular';
            font-weight: 400;
            color: #3888ff;
            line-height: 20px;
            transition: 0.2s;
            text-decoration: none;
        }
        .error_btn :hover {
            background-color: #3888ff;
            color: #ffffff;
        }

    </style>
</head>
<body>
<div class="undefined_contain">
    {{-- <image class="image" src="/assets/image/img_def_404.png" /> --}}
    <div class="error_title">页面异常</div>
    <div class="error_desc">抱歉！找不到相关的内容</div>
    <a class="error_btn" href="/index">返回首页</a>
</div>
</body>
</html>