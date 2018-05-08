#在线消息管理
##目的:
- 主要用于实现网站站内信消息功能；
- 基于Smartadmin静态页面功能进行开发；

##设计思路
 初步设定以下几个字段，进行页面信息展示；
 - 用户uid, 默认0
 - 标题 title, 默认''
 - 内容 content, 默认''
 - 时间 created,默认0
 - 是否阅读 isread, 默认false
 - 删除标志 isdeleted, 默认false

##系统变量
  - 无
##请求设计
###自动请求
  -----------
  - ajax/smart/tips/msgs/list: 在smartadmin中使用ajax显示最新的消息列表
  - ajax/smart/tips/msgs/list/personal: 在smartadmin中使用ajax显示最新的个人消息列表
  - ajax/smart/tips/msgs/autocomplete: 系统程序根据某种形态直接调用消息发送，比如订单生成后的通知？或者其他 ### 暂时保留这个功能
  - user/{id}/smart/tips/msgs/delete: 前台个人用户消息功能
  - user/{id}/smart/tips/msgs/readflag: 个人用户标记已读
###后台
  -----------
  - admin/smart/tips/msgs/add: 后台系统用户手动发送消息
  - admin/smart/tips/msgs/{mid}/edit: 后台系统用户手动发送消息
  - admin/smart/tips/msgs/delete: 后台系统用户手工删除，包括单个和批量删除
  - admin/smart/tip/msgs/statistic: 消息阅读统计
###前台
  -----------
  - user/{uid}/smart/tips/msgs/list: 前端用户可以在此阅读
  - user/{uid}/smart/tips/msgs/{mid}/delete: 用户删除信息

##权限设计
  -----------
  ajax smart tips msgs autocomplete 允许自动生成新的消息，并通知所有人 ###这个要考虑如何进行，发送给什么样的角色或用户！暂时保留这个功能
  administration smart tips msgs edit 管理人员的消息编辑功能，即添加和编辑
  administration smart tips msgs delete all 管理人员的物理删除功能
  administration smart tips msgs statistics 管理人员的消息统计管理
  access smart tips msgs delete all 管理人员或个人用户的消息批量删除
  administrator smart tips list 允许访问msgs的ajax动态列表
  access smart tips msgs ajax personal list  允许访问msgs的ajax动态列表
##对象设计
  null
##数据库设计
 - 消息ID id,自动生成
 - uuid,自动生成
 - langcode
 - 接收用户uid, 默认0
 - 创建用户cid, 默认0
 - 时间 created,默认0
 - 是否阅读 isread, 默认false
 - 删除标志 isdeleted, 默认false
 - 更改时间,changed
---------------
 - 标题 title, 默认''
 - 内容 content, 默认''
