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

[root@ac_web swoole-worker-task]# php service.php start Amazon pullOrder 11001 100
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
root      6057  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-21
root      6058  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-22
root      6059  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-23
root      6060  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-24
root      6061  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-25
root      6062  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-26
root      6063  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-27
root      6064  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-28
root      6065  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-29
root      6066  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-30
root      6067  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-31
root      6068  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-32
root      6069  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-33
root      6070  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-34
root      6071  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-35
root      6072  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-36
root      6073  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-37
root      6074  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-38
root      6075  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-39
root      6076  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-40
root      6077  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-41
root      6078  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-42
root      6079  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-43
root      6080  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-44
root      6081  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-45
root      6082  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-46
root      6083  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-47
root      6084  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-48
root      6085  0.0  0.1 624108  8712 ?        S    11:45   0:00 task-Amazon-11001-worker-49
root      6137  0.0  0.1 624248  8972 ?        S    11:45   0:00 task-Amazon-11001-worker-0
root      6270  0.0  0.0 112736   976 pts/0    S+   11:45   0:00 grep --color=auto task
[root@ac_web swoole-worker-task]# php service.php sendTask Amazon pullOrder
[2023-03-16 11:46:51]sendTask done

```


