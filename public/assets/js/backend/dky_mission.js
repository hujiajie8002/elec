define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'dky_mission/index' + location.search,
                    add_url: 'dky_mission/add',
                    edit_url: 'dky_mission/edit',
                    del_url: 'dky_mission/del',
                    multi_url: 'dky_mission/multi',
                    import_url: 'dky_mission/import',
                    table: 'dky_mission',
                    //fastadmin中的表格传参
                    bind_url:"dky_mission_experiment/index?mission={id}",
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
                        //{field: 'sample_code', title: __('Sample_code'), operate: 'LIKE'},
                        //{field: 'sample_index', title: __('Sample_index'), operate: 'LIKE'},
                        {field: 'testing_institution', title: __('Testing_institution'), operate: 'LIKE'},
                        {field: 'twins_token', title: __('Twins_token'), operate: 'LIKE'},
                        //{field: 'status', title: __('Status'), operate: 'LIKE', formatter: Table.api.formatter.status},
                        //{field: 'uwb_token', title: __('Uwb_token'), operate: 'LIKE'},
                        {field: 'device_type_belong', title: __('Device_type_belong'), operate: 'LIKE'},
                        {field: 'device_type', title: __('Device_type'), operate: 'LIKE'},

                        {field: 'bind', title: __('operate'),
                            operate:false,
                            buttons:[{name:'bind',
                                text:'',
                                title:__('关联管理'),
                                //在requre-table.js中576行添加btn-show类按钮的点击事件为弹窗事件
                                classname:'btn btn-xs btn-info btn-bind',
                                icon:'fa fa-wrench',
                            }],table: table, events: Table.api.events.operate,
                            //屏蔽表记录operate中的编辑和删除功能
                            formatter: function (value, row, index) {
                                var that = $.extend({}, this);
                                var table = $(that.table).clone(true);
                                $(table).data("operate-edit", false);
                                $(table).data("operate-del", false);
                                that.table = table;
                                return Table.api.formatter.operate.call(that, value, row, index);
                            },
                        },
                        //{field: 'distribute_time', title: __('Distribute_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        //{field: 'finish_time', title: __('Finish_time'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        //{field: 'experiment_type', title: __('Experiment_type'), operate: 'LIKE'},

                        //{field: 'testing_duration', title: __('Testing_duration'), operate:'BETWEEN'},
                        //{field: 'overtime_norm', title: __('Overtime_norm')},
                        //{field: 'overtime_duration', title: __('Overtime_duration'), operate:'BETWEEN'},
                        //{field: 'conclusion', title: __('Conclusion')},
                        //{field: 'create_at', title: __('Create_at'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        //{field: 'update_at', title: __('Update_at')},
                        //{field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
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