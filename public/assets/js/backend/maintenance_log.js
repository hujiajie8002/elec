define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'maintenance_log/index' + location.search,
                    add_url: 'maintenance_log/add',
                    edit_url: 'maintenance_log/edit',
                    del_url: 'maintenance_log/del',
                    multi_url: 'maintenance_log/multi',
                    import_url: 'maintenance_log/import',
                    table: 'maintenance_log',
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
                        //{field: 'unity_token', title: __('Unity_token'), operate: 'LIKE'},
                        {field: 'district_id', title: __('District_id'),formatter: show_district},
                        {field: 'device_no', title: __('Device_no')},
                        {field: 'type', title: __('Type'), searchList: {"检测设备":__('检测设备'),"工位":__('工位'),"AGV设备":__('Agv设备'),"仓储设备":__('仓储设备')}},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'number', title: __('Number'), operate: 'LIKE'},
                        {field: 'maintenance_at', title: __('Maintenance_at'), operate:'RANGE', addclass:'datetimerange', autocomplete:false},
                        {field: 'maintenance_by', title: __('Maintenance_by'), operate: 'LIKE'},
                        {field: 'operator', title: __('Operator'), operate: 'LIKE'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        //{field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
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
                url: 'maintenance_log/recyclebin' + location.search,
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
                                    url: 'maintenance_log/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'maintenance_log/destroy',
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