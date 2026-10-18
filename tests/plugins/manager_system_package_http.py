"""manager整站打包→独立安装→初始化→登录→安装manager→在线／离线升级。

复用当前源码HTTPS夹具；所有打包、解包、设置和注册只发生在随机临时目录。
PHP7.4、Python3.9+、OpenSSL；MYSTEP_RELEASE_REPORT可指定JSON报告文件。
"""
from pathlib import Path
import os,runpy
os.environ['MYSTEP_SYSTEM_PACKAGE']='1'
runpy.run_path(str(Path(__file__).with_name('manager_current_release_http.py')),run_name='__main__')
