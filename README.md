# File transfers

#### 介绍
可用于不同设备之间进行文件的传输，专门为内网用户不同设备间的文件传输，支持用户区分上传与下载。

#### 软件架构
> 基于php+mysql+bootstrap进行开发。
>

#### 安装教程

1. 首先将整个压缩包进行解压，找到名为“phpEnv8.9.6-Setup.exe”的文件，双击进行安装。（[压缩包版本，由于Gitee最大支持上传100MB，所以不再上传安装包版本，按住键盘Ctrl键并点击即可下载](https://dl.phpenv.cn/release/phpEnv.7z)）

   

   ```https
   https://dl.phpenv.cn/release/phpEnv.7z
   或者直接复制此连接到浏览器访问
   ```

2. 安装时最好选择D盘或者E盘的根目录，方便后续使用。

3. 将文件夹内的“`tools.zip`”压缩包解压后，放入到刚才安装的软件的目录中。

4. 然后打开刚才安装的软件“`phpEnv`”。

5. 点击左上角应用软件→设置→端口，将Nginx端口改为非占用端口，例如：7890

6. 改完以后，到首页，点击 工具→MySQL工具→重置密码，输入密码，点击确定。

7. 点击网站按钮，点击添加按钮，域名输入服务器ip，端口输入刚才设置的端口后面的端口，例如：7891，点击添加。

8. 右键新建的站点，进入根目录，打开开始的文件夹，将里面内容全部复制到新站点的根目录。

9. 配置`config.json`文件，配置数据库连接信息。

   

   ```json
   "database": "",--数据库名称，例如wjcs
   "username": "",--数据库登录用户名，默认为root
   "password": "" --数据库登录密码，刚才重置密码时的密码
   ```

   

10. 回到软件的首页，点击右下角数据库，会打开数据库连接工具“`HeidiSQL`”。

11. 在右侧输入密码，点击连接，如果连接成功，会看到数据库列表。

12. 右键点击左侧`localhost`，创建新的→数据库，名称则输入`config.json`文件内配置的数据库名称 例如：wjcs。

13. 然后打开浏览器，输入：IP:端口，例如：`192.168.109.131:7891`，如果能打开页面，说明配置成功。

14. 在端口后面加上`/sql.php`，例如：`192.168.109.131:7891/sql.php`，自动创建数据库，创建完成后，会提示创建成功等字样。

15. 重新访问站点的首页，用户名：`admin`   密码：`123qwe`
    --至此安装配置完成--

#### 使用说明

1.  新增和删除用户只有管理员身份可以操作，登陆后，点击右上角的管理用户，然后点击左上角的新增用户，即刻会跳转至注册用户的界面，也可以点击下面的上传用户进行批量的注册用户。
2.  批量注册用户，分为两种方式，一种是上传xlxs文件，一种是粘贴用户信息。可通过点击换方式按钮来选择想要使用的方式。
3.  在上传用户的界面，可以点击下载模板文件，去填写用户信息，填写完之后，可以上传此文件，也可以将所有用户部分复制下来，粘贴至粘贴框，然后点击提交即可。
4.  用户批量创建成功的话，下方会显示所有用户有没有上传成功！
5.  界面适配手机端，手机也可以通过ip:端口进行访问。
6.  文件最大上传限制为300MB，首页顶部也会有弹窗提醒。
7.  初始管理员账号：admin   密码：123qwe

#### 修改配置
如何修改文件上传大小的限制？
1.  修改`index.php`文件内的
    
    
    
    ```php
    const maxSize = 300 * 1024 * 1024; // 300MB
    if (file.size > maxSize) {
             displayMessage('文件大小超过限制 (最大300MB)。', 'warning');
             return;
    }
    ```
    
    ​    可以将300改为3000，这样就改为限制3GB，修改之后保存！




2. 修改`file_manager.php`文件内的

   

   ```PHP
   // 文件大小限制
           $maxFileSize = 300 * 1024 * 1024; // 300MB
           if ($filesize > $maxFileSize) {
           $responses[] = ['success' => false, 'error' => '文件大小超过限制 (最大300MB)。'];
           continue;
           }
   ```

   ​    也将300改为3000，要和`index.php`中的改为一致，修改之后保存！

3. 打开phpEnv软件，鼠标放到上面的服务那里，修改PHP的`php.ini`文件中的

   

   ```ini
   upload_max_filesize = 300M （允许上传的最大文件大小）
   post_max_size = 310M （允许接收的最大表单数据大小，应大于或等upload_max_filesize）
   max_execution_time = 300 （增加脚本执行和输入解析的时间，防止大文件上传时超时）
   max_input_time = 300 （增加脚本执行和输入解析的时间，防止大文件上传时超时）
   memory_limit = 512M （确保 PHP 有足够的内存处理大文件）
   ```

   ​            这些根据自己需求来设置，修改之后保存！

4. 修改Nginx的`nginx.conf`文件中的
              

   ```conf
   client_max_body_size 300M  （nginx服务的上传限制）
   ```

   修改之后保存！

5. 修改`index.php`中的下方代码，这里是首页上方的弹窗提示，改了多少，这里就改为多少，改完保存！

    

   ```php+HTML
   <!-- 系统使用公告 -->
   <div class="alert alert-info alert-dismissible fade show text-center announcement animate__animated animate__fadeInDown" role="alert">
        文件最大限制300MB哦，多1kb都不行哦😁！
        <link rel="stylesheet" href="css/animate.min.css"/>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="关闭"></button>
   </div>
   ```

   >
   > （现在已改为读取数据库内的内容）

6. 回到软件的首页，点击右边的重启服务按钮，等待重启完毕即可！