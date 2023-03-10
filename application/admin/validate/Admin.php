<?php

namespace app\admin\validate;

use think\Validate;

class Admin extends Validate
{

    /**
     * 验证规则
     */
    protected $rule = [
        'username' => 'require|regex:\w{3,12}|unique:admin',
        'nickname' => 'require',
        'email'    => 'require|email|unique:admin,email',
    ];

    /**
     * 提示消息
     */
    protected $message = [
    ];

    /**
     * 字段描述
     */
    protected $field = [
    ];

    /**
     * 验证场景
     */
    protected $scene = [
        'add'  => ['username', 'email', 'nickname', 'password'],
        'edit' => ['username', 'email', 'nickname', 'password'],
    ];

    public function __construct(array $rules = [], $message = [], $field = [])
    {
        $this->field = [
            'username' => __('Username'),
            'nickname' => __('Nickname'),
            'email'    => __('Email'),
        ];
        $this->message = array_merge($this->message, [
            'username.regex' => __('Please input correct username'),
        ]);
        parent::__construct($rules, $message, $field);
    }

}
