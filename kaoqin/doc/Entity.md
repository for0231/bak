数据库实体分为两个部分:
1. 考勤打卡记录实体
2. 考勤计划排班计划实体--排班制的计划将会保存在此实体中。

1. 考勤打卡实体字段:
- id
- uuid
- code: 人员编号
- emname: 姓名
- logdate: 考勤日期
- weekday: 星期
- banci: 班次
- morningsign: 上班打卡时间
- afternoonsign: 下班打卡时间
- uid:
- created:
- changed:
- langcode:

2. 考勤排班计划
- id
- uuid
- type: 班次
- icontype: 事件类型图标
- allday: 全天事件
- iconcolor: 事件颜色
- depart: 部门
- user: 申请人
- datetime: 月份
- morningsign: 上班时间
- afternoonsign: 下班时间
- description: 描述
- uid
- created
- changed
- langcode
