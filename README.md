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
[root@ac_web swoole-worker-task]# ps aux|grep task-Amazon|grep -v grep
root     18148  0.0  0.1 594408 11940 ?        Ssl  12:30   0:00 task-Amazon-11001-master
root     18149  0.1  0.1 520608  8736 ?        S    12:30   0:00 task-Amazon-11001-manager
root     18151  0.0  0.1 522660  8712 ?        S    12:30   0:00 task-Amazon-11001-taskworker-1
root     18152  0.0  0.1 522660  8704 ?        S    12:30   0:00 task-Amazon-11001-taskworker-2
root     18153  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-3
root     18154  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-4
root     18155  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-5
root     18156  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-6
root     18157  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-7
root     18158  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-8
root     18159  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-9
root     18160  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-10
root     18161  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-11
root     18162  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-12
root     18163  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-13
root     18164  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-14
root     18165  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-15
root     18166  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-16
root     18167  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-17
root     18168  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-18
root     18169  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-19
root     18170  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-20
root     18171  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-21
root     18172  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-22
root     18173  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-23
root     18174  0.0  0.1 522660  8736 ?        S    12:30   0:00 task-Amazon-11001-taskworker-24
root     18175  0.0  0.1 522660  8736 ?        S    12:30   0:00 task-Amazon-11001-taskworker-25
root     18176  0.0  0.1 522660  8736 ?        S    12:30   0:00 task-Amazon-11001-taskworker-26
root     18177  0.0  0.1 522660  8736 ?        S    12:30   0:00 task-Amazon-11001-taskworker-27
root     18178  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-28
root     18179  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-29
root     18180  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-30
root     18181  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-31
root     18182  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-32
root     18183  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-33
root     18184  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-34
root     18185  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-35
root     18186  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-36
root     18187  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-37
root     18188  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-38
root     18189  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-39
root     18190  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-40
root     18191  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-41
root     18192  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-42
root     18193  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-43
root     18194  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-44
root     18195  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-45
root     18196  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-46
root     18197  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-47
root     18198  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-48
root     18199  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-49
root     18200  0.0  0.1 522660  8732 ?        S    12:30   0:00 task-Amazon-11001-taskworker-50
root     18201  0.0  0.1 522820  8948 ?        S    12:30   0:00 task-Amazon-11001-worker-0
[root@ac_web swoole-worker-task]#
[root@ac_web swoole-worker-task]#
[root@ac_web swoole-worker-task]# php service.php sendTask Amazon pullOrder
[2023-03-16 11:46:51]sendTask done

```

#### 多模块任务执行（不同类型，端口，task任务数）
```shell script
[root@ac_web swoole-worker-task]# php service.php start Amazon pullOrder 11001 100
[root@ac_web swoole-worker-task]# php service.php start Amazon checkOrder 11002 100
[root@ac_web swoole-worker-task]# php service.php start Amazon checkException 11003 50
[root@ac_web swoole-worker-task]# php service.php start Shopee pullOrder 12001 50
[root@ac_web swoole-worker-task]# php service.php start Shopee checkOrder 12002 50
[root@ac_web swoole-worker-task]# php service.php start Shopee checkException 12003 50

#进程管理
#默认使用SWOOLE_PROCESS模式，因此会额外创建Master和Manager两个进程。在设置worker_num之后，实际会出现2 + worker_num + task_worker_num个进程
#服务器启动后，可以通过kill 主进程ID来结束所有工作进程
```
