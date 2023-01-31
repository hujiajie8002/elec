define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'rank/index' + location.search,
                    add_url: 'rank/add',
                    edit_url: 'rank/edit',
                    del_url: 'rank/del',
                    multi_url: 'rank/multi',
                    import_url: 'rank/import',
                    table: 'rank',
                    rank_list_url:'rank_breakdown/index?rank_token={id}'
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
                        {field: 'year', title: __('Year')},
                        {field: 'quarter', title: __('Quarter'), searchList: {"第一季度":__('第一季度'),"第二季度":__('第二季度'),"第三季度":__('第三季度'),"第四季度":__('第四季度')}},
                        {field: 'institution', title: __('Institution'), searchList: {"省中心（电科院）":__('省中心（电科院）'),"苏南分中心":__('苏南分中心'),"苏中分中心":__('苏中分中心'),"苏北分中心":__('苏北分中心')}},
                        {field: 'score', title: __('Score'), operate:'BETWEEN'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        //{field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                        {field: 'bind', title: __('operate'),
                            operate:false,
                            buttons:[{name:'bind',
                                text:'',
                                title:__('扣分细目'),
                                //在requre-table.js中576行添加btn-show类按钮的点击事件为弹窗事件
                                classname:'btn btn-xs btn-info btn-rank-list',
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