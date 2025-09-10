#!/usr/bin/python3
# coding: utf-8
import psutil
import sys

def cpu_usage(interval = 1):
    # interval = time in seconds between each measurement
    usage = psutil.cpu_percent(interval=interval)
    return usage

def cpu_usage_per_core(interval = 1):
    usage = psutil.cpu_percent(interval=interval, percpu=True)
    return usage

try:
    # print("Global CPU usage: ", cpu_usage(), "%")
    # print("CPU usage per core: ", cpu_usage_per_core(), "%")

    print(cpu_usage())
except Exception as e:
    print("Error while getting CPU usage:", e)
    sys.exit(1)

sys.exit(0)