define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'rank_breakdown/index' + location.search,
                    add_url: 'rank_breakdown/add'+'?rank_token='+$('#rank_token').val(),
                    edit_url: 'rank_breakdown/edit',
                    del_url: 'rank_breakdown/del',
                    multi_url: 'rank_breakdown/multi',
                    import_url: 'rank_breakdown/import',
                    table: 'rank_breakdown',
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
                        {field: 'rank_token', title: __('Rank_token')},
                        {field: 'year', title: __('Year')},
                        {field: 'quarter', title: __('Quarter'), searchList: {"第一季度":__('第一季度'),"第二季度":__('第二季度'),"第三季度":__('第三季度'),"第四季度":__('第四季度')}, formatter: Table.api.formatter.normal},
                        {field: 'institution', title: __('Institution'), searchList: {"省中心（电科院）":__('省中心（电科院）'),"苏南分中心":__('苏南分中心'),"苏中分中心":__('苏中分中心'),"苏北分中心":__('苏北分中心')}, formatter: Table.api.formatter.normal},
                        {field: 'name', title: __('Name'), operate: 'LIKE'},
                        {field: 'score', title: __('Score'), operate:'BETWEEN'},
                        {field: 'num', title: __('Num')},
                        {field: 'sum', title: __('Sum'), operate:'BETWEEN'},
                        {field: 'content', title: __('Content'), operate: 'LIKE'},
                        //{field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                       // {field: 'updatetime', title: __('Updatetime'), operate:'RANGE', addclass:'datetimerange', autocomplete:false, formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            function switchOperate(value,row,index){

            }


            // 为表格绑定事件
            Table.api.bindevent(table);

            Table.api.init({
                extend: {
                    index_url: 'rank_breakdown/index' + location.search,
                    add_url: 'rank_breakdown/add'+'?rank_token='+$('#rank_token').val(),
                    edit_url: 'rank_breakdown/edit',
                    del_url: 'rank_breakdown/del',
                    multi_url: 'rank_breakdown/multi',
                    import_url: 'rank_breakdown/import',
                    table: 'rank_breakdown',
                }
            });


        },
        add: function () {
            //获取rank_token值，自动填充
            function GetQueryString(name) {
                var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)","i");
                var r = window.location.search.substr(1).match(reg);
                if (r!=null) return (r[2]); return null;
            }
            let rank_token = GetQueryString('rank_token')
            $("#c-rank_token").val(rank_token);


            let list = Config.list;
            let name = $("#c-name").val();
            for (let i in list){
                if (list[i]['name'] == name){
                    $("#c-score").val(list[i]['score']);
                }
            }


            $('#c-name').on('change',function (){
                let name = $("#c-name").val();
                for (let i in list){
                    if (list[i]['name'] == name){
                        $("#c-score").val(list[i]['score']);
                    }
                }

            });

            $('#c-num,#c-score').on('change',function (){
                let num = $('#c-num').val();
                let score = $('#c-score').val();
                $('#c-sum').val(num * score);
            });
            Controller.api.bindevent();
        },
        edit: function () {

            let list = Config.list;
            let name = $("#c-name").val();
            for (let i in list){
                if (list[i]['name'] == name){
                    $("#c-score").val(list[i]['score']);
                }
            }


            $('#c-name').on('change',function (){
                let name = $("#c-name").val();
                for (let i in list){
                    if (list[i]['name'] == name){
                        $("#c-score").val(list[i]['score']);
                    }
                }

            });

            $('#c-num,#c-score').on('change',function (){
                let num = $('#c-num').val();
                let score = $('#c-score').val();
                $('#c-sum').val(num * score);
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