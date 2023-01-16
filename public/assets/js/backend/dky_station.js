define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'dky_station/index' + location.search,
                    add_url: 'dky_station/add',
                    edit_url: 'dky_station/edit',
                    del_url: 'dky_station/del',
                    multi_url: 'dky_station/multi',
                    import_url: 'dky_station/import',
                    table: 'dky_station',
                    //fastadmin中的表格传参
                    show_url:"maintenance_log/index?device_no={device_no}",
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

                        {field: 'district_id', title: __('District_id'),formatter: show_district,operate: false},
                        {field: 'device_no', title: __('device_no')},
                        {field: 'unity_token', title: __('Unity_token'), operate: 'LIKE'},
                        {field: 'station_token', title: __('Station_token'), operate: 'LIKE'},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'model_number', title: __('Model_number'), operate: 'LIKE'},
                        {field: 'zcbh', title: __('Zcbh'), operate: 'LIKE'},
                        {field: 'company', title: __('Company'), operate: 'LIKE'},
                        {field: 'experiment_ids', title: __('Experiment_ids'), operate: 'LIKE'},
                        {field: 'principal', title: __('Principal'), operate: 'LIKE'},
                        {field: 'uwb_token', title: __('Uwb_token'), operate: 'LIKE'},
                        {field: 'createtime', title: __('Createtime'),  operate: false,addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        //{field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},

                        {field: 'maintenance', title: __('maintenance'),
                            buttons:[{name:'show',
                                text:'',
                                title:__('运维记录'),
                                //在requre-table.js中576行添加btn-show类按钮的点击事件为弹窗事件
                                classname:'btn btn-xs btn-info btn-show',
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
                            },operate: false
                        },
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate},
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
                url: 'dky_station/recyclebin' + location.search,
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
                                    url: 'dky_station/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'dky_station/destroy',
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