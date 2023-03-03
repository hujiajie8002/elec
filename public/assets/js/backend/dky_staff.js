define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'dky_staff/index' + location.search,
                    add_url: 'dky_staff/add',
                    edit_url: 'dky_staff/edit',
                    del_url: 'dky_staff/del',
                    multi_url: 'dky_staff/multi',
                    import_url: 'dky_staff/import',
                    table: 'dky_staff',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'district_id', title: __('District_id'),formatter: show_district},
                        //{field: 'unity_token', title: __('Unity_token'), operate: 'LIKE'},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'avatar', title: __('Avatar'), operate: 'LIKE', events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'sex', title: __('Sex'), searchList: {"男":__('男'),"女":__('女')}},
                        {field: 'number', title: __('Number'), operate: 'LIKE'},
                        {field: 'job', title: __('Job'), operate: 'LIKE'},
                        {field: 'skill_rank', title: __('Skill_rank'), searchList: {"Level-1":__('Level-1'),"Level-2":__('Level-2'),"Level-3":__('Level-3'),"Level-4":__('Level-4'),"Level-5":__('Level-5'),"Level-6":__('Level-6'),"Level-7":__('Level-7')}, formatter: Table.api.formatter.normal},
                        {field: 'type', title: __('Type'), searchList: {"A级检测人员":__('A级检测人员'),"B级检测人员":__('B级检测人员')}},
                        {field: 'property', title: __('Property'), searchList: {"设备":__('设备'),"材料":__('材料')}},
                        {field: 'entry_at', title: __('Entry_at'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'working_years', title: __('Working_years'),operate: 'between'},
                        {field: 'uwb_token', title: __('Uwb_token'), operate: 'LIKE'},
                        {field: 'nzrq', title: __('Nzrq'),  addclass:'datetimerange', autocomplete:false},
                        {field: 'yxq', title: __('Yxq'),  addclass:'datetimerange', autocomplete:false},
                        {field: 'status', title: __('Status'), searchList: {"0":__('Status 0'),"1":__('Status 1'),"2":__('Status 2')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('Createtime'), operate: false, addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'),  operate: false,addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });


            //将机构数字变为可读性具体机构名
            function show_district(value,row,index)
            {
                switch(row.district_id){
                    case 1:
                        value='省中心（电科院）';
                        break;
                    case 2:
                        value='苏南分中心';
                        break;
                    case 3:
                        value='苏中分中心';
                        break;
                    case 4:
                        value='苏北分中心';
                        break;

                }

                return value;
            }
            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        recyclebin: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    'dragsort_url': ''
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: 'dky_staff/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'name', title: __('Name'), align: 'left'},
                        {
                            field: 'deletetime',
                            title: __('Deletetime'),
                            operate: 'RANGE',
                            addclass: 'datetimerange',
                            formatter: Table.api.formatter.datetime
                        },
                        {
                            field: 'operate',
                            width: '130px',
                            title: __('Operate'),
                            table: table,
                            events: Table.api.events.operate,
                            buttons: [
                                {
                                    name: 'Restore',
                                    text: __('Restore'),
                                    classname: 'btn btn-xs btn-info btn-ajax btn-restoreit',
                                    icon: 'fa fa-rotate-left',
                                    url: 'dky_staff/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'dky_staff/destroy',
                                    refresh: true
                                }
                            ],
                            formatter: Table.api.formatter.operate
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },

        add: function () {
            $('#faupload-avatar').on('click',function (){
                layer.msg('请选择图片格式',{
                    icon: 1,
                    time: 10000 //2秒关闭（如果不配置，默认是3秒）
                });
            });
            Controller.api.bindevent();
        },
        edit: function () {
            $('#faupload-avatar').on('click',function (){
                layer.msg('请选择图片格式',{
                    icon: 1,
                    time: 10000 //2秒关闭（如果不配置，默认是3秒）
                });
            });
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});