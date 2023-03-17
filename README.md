# swoole-worker-task
swoole-worker-task异步任务，使用mongoDB存储，下载订单发送rabbitmq队列给订单模块消费。  
可指定模块，任务类型，端口，异步任务数 

#### 服务端启动

```shell script
# php service.php [action] [taskType] [type] [port] [taskNum]

php service.php start Amazon pullOrder 11001 50

php service.php stop Amazon pullOrder

```

#### 客户端，定时任务或命令行触发
```shell script
# php service.php [action] [taskType] [type]

php service.php sendTask Amazon pullOrder

```

#########
```shell script

[root@ac_web swoole-worker-task]# php service.php start Amazon pullOrder 11001 20
[root@ac_web swoole-worker-task]# 
[root@ac_web swoole-worker-task]# 
[root@ac_web swoole-worker-task]# ps aux|grep task
root        30  0.0  0.0      0     0 ?        S     2021   0:20 [khungtaskd]
root      6033  0.0  0.1 693784 11800 ?        Ssl  11:45   0:00 task-Amazon-11001-master
root      6034  0.3  0.1 622056  8684 ?        S    11:45   0:00 task-Amazon-11001-manager
root      6036  0.0  0.1 624108  8708 ?        S    11:45   0:00 task-Amazon-11001-worker-1
root      6037  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-2
root      6038  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-3
root      6039  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-4
root      6040  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-5
root      6041  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-6
root      6042  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-7
root      6043  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-8
root      6044  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-9
root      6045  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-10
root      6046  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-11
root      6047  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-12
root      6048  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-13
root      6049  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-14
root      6050  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-15
root      6051  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-16
root      6052  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-17
root      6053  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-18
root      6054  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-19
root      6056  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-20
root      6137  0.0  0.1 624248  8972 ?        S    11:45   0:00 task-Amazon-11001-worker-0
root      6270  0.0  0.0 112736   976 pts/0    S+   11:45   0:00 grep --color=auto task
[root@ac_web swoole-worker-task]#
[root@ac_web swoole-worker-task]#
[root@ac_web swoole-worker-task]# php service.php sendTask Amazon pullOrder
[2023-03-16 11:46:51]sendTask done

#进程管理
#默认使用SWOOLE_PROCESS模式，因此会额外创建Master和Manager两个进程。在设置worker_num之后，实际会出现2 + worker_num个进程
#服务器启动后，可以通过kill 主进程ID来结束所有工作进程

```


