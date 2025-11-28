<?php

$app = app()->router;
$app->group([
    'namespace' => 'App\Attachment\Controllers',
    'middleware' => ['auth'],
], function ($app) {

    //上传附件
    $app->post('/admin/attachment/uploadFile', 'AttachmentController@upload');
    //上传附件
    $app->post('/admin/attachment/upload', 'AttachmentController@upload');
    //图片上传 base64
    $app->post('/admin/attachment/upload-image', 'AttachmentController@uploadImg');
    //获取附件列表
    $app->get('/admin/attachment/attachment_list','AttachmentController@attachmentList');
    //保存附件
    $app->post('/admin/attachment/save_attachment','AttachmentController@saveAttachment');
    //删除附件
    $app->delete('/admin/attachment/del_attachment','AttachmentController@delAttachment');
    //批量删除附件
    $app->post('/admin/attachment/batch_del_attachment','AttachmentController@batchDelAttachment');

});
