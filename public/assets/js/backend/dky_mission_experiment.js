define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'dky_mission_experiment/index' + location.search,
                    add_url: 'dky_mission_experiment/add',
                    edit_url: 'dky_mission_experiment/edit',
                    //del_url: 'dky_mission_experiment/del',
                    multi_url: 'dky_mission_experiment/multi',
                    import_url: 'dky_mission_experiment/import',
                    table: 'dky_mission_experiment',
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
                        {field: 'mission', title: __('Mission')},
                        //{field: 'sample_index', title: __('Sample_index'), operate: 'LIKE'},
                        {field: 'experiment', title: __('Experiment'), operate: 'LIKE'},
                        {field: 'dky_staff_ids', title: __('Dky_staff_ids'), operate: 'LIKE',formatter: show_staff},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        //{field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('分配检验人'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            function show_staff(value,row,index)
            {
                let staff = [];
                $.ajax({
                    type:'post',
                    url:'dky_staff/getStaff',
                    async:false,
                    dataType:'json',
                    success:function (data_json){
                        staff = JSON.parse(data_json);
                    },
                    error:function (){
                        alert('网络繁忙，请稍后重试');
                    }
                });
                let ids = row.dky_staff_ids;
                let staff_name_str = '';
                if (ids){
                    let ids_arr = ids.split(',')
                     staff_name_str = '';
                    if (ids_arr){
                        for(let i in ids_arr){
                            console.log(ids_arr[i])
                            console.log(staff[ids_arr[i]]['name'])
                            staff_name_str += staff[ids_arr[i]]['name']+',';

                        }
                    }
                    return staff_name_str.slice(0,staff_name_str.length-1);
                }else{
                    return '-'
                }


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
                url: 'dky_mission_experiment/recyclebin' + location.search,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
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
                                    url: 'dky_mission_experiment/restore',
                                    refresh: true
                                },
                                {
                                    name: 'Destroy',
                                    text: __('Destroy'),
                                    classname: 'btn btn-xs btn-danger btn-ajax btn-destroyit',
                                    icon: 'fa fa-times',
                                    url: 'dky_mission_experiment/destroy',
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