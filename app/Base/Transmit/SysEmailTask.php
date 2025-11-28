<?php
namespace App\Base\Transmit;

/**
 * 邮件发送任务
 */
class SysEmailTask
{
    /** @var int 关联数据ID */
    public $relativeId;
    /** @var string 关联的KEY, 模块_动作 */
    public $relativeTable;
    /** @var string 短信模板 */
    public $template;
    /** @var string 邮件地址 */
    public $toEmail;
    /** @var string 主题 */
    public $subject;
    /** @var array 短信数据 */
    public $templateData;
    /** @var string 计划发送时间 */
    public $planTime;
}
