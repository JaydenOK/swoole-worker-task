# swoole-worker-task
swoole-worker-task异步任务，使用mongoDB存储

#### 服务端启动

```shell script
# php service.php [action] [taskType] [type] [port] [taskNum]

php service.php start Amazon pullOrder 11001 20

```

#### 客户端，定时任务执行
```shell script
# php service.php [action] [taskType] [type]

php service.php sendTask Amazon pullOrder

```


