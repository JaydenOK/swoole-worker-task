# swoole-worker-task
swoole-worker-task

#### 服务端启动
```shell script
php service.php [action] [taskType] [type] [port] [taskNum]

php service.php start Amazon pullOrder 11001 50

```



#### 客户端，定时任务执行
```shell script
php service.php sendTask Amazon pullOrder
```


