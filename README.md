<h1 align="center">Standard Pure PHP Architecture<br/>
    The fastest source code for building website in a short time !
</h1>

<p align="center">
    <img src="./avatar/cover-wallpaper.jpg" width="1280" />
</p>


# [**Table Of Content**](#table-of-content)
- [**Table Of Content**](#table-of-content)
- [**Initialization**](#initialization)
- [**Installation**](#installation)
- [**Controller**](#controller)
- [**Made with 💘 and PHP <img src="https://www.vectorlogo.zone/logos/php/php-horizontal.svg" width="60">**](#made-with--and-php-)

# [**Initialization**](#initialization)

(1) Chọn Code -> Download ZIP để tải mã nguồn mở này về máy. Sau khi giải nén mã nguồn sẽ có hình như sau đây:

<p align="center">
    <img src="./avatar/screenshot1.png" width="960" />
</p>

(2) Để sử dụng mã nguồn này hãy sao chép và dán tệp tin nén `nextpost.zip` vào thư mục cài đặt `xampp` và giải nén tệp tin này ra.

(3) Sau khi giải nén ra thì sẽ có dạng như sau: `nextpost` -> `nextpost` -> `source code` 

(4) chép `source code` ra thư mục `nextpost` gốc và xóa thư mục còn lại đi. Lúc này thư mục sẽ còn dạng: `nextpost` -> `source code` như hình dưới đây:

<p align="center">
    <img src="./avatar/screenshot2.png" width="960" />
</p>

Đổi tên nextpost thành bất cứ tên dự án nào theo mong muốn

# [**Installation**](#installation)

(1) Khởi động XAMPP và bật dịch vụ Apache và MySQL lên

(2) Vào đường dẫn http://localhost/nextpost (nếu tên nextpost vẫn được giữ nguyên) sẽ có màn hình như sau:

<p align="center">
    <img src="./avatar/screenshot3.png" width="640" />
</p>

(3) Chọn **START INSTALLATION** và chọn *I read and accept the agreement !*, nhấn NEXT cho tới khi hiện ra màn hình sau:

<p align="center">
    <img src="./avatar/screenshot4.png" width="640" />
</p>

- **DATABASE NAME**: tên cơ sở dữ liệu trong phpmyadmin. Giả sử ta muốn kết nối với cơ sở dữ liệu tên `Forever21` thì việc đầu tiên cần làm
là vào phpmyadmin, tạo một cơ sở dữ liệu tên `Forever21` sau đó chép tên cơ sở dữ liệu này và dán vào ô Database Name 

- **USERNAME**: tên tài khoản đăng nhập vào cơ sở dữ liệu trên. Theo mặc định là `root`.

- **PASSWORD**: mật khẩu đăng nhập vào cơ sở dữ liệu trên. Theo mặc định là để trống.


<p align="center">
    <img src="./avatar/screenshot5.png" width="640" />
</p>

- **TABLE PREFIX**: tên tiền tố của các bảng. Giả sử ta đặt là np_ thì mọi bảng trong cơ sở dữ liệu sẽ có tên như sau đây:

<p align="center">
    <img src="./avatar/screenshot6.png" width="640" />
</p>

- **ADMINISTRATIVE ACCOUNT DETAIL** là những thông tin cơ bản để tạo tài khoản quản trị viên. Tài khoản này sẽ dùng để đăng nhập vào hệ thống này sau khi quá trình cài đặt thành công.

(4) Qúa trình kết thúc và một màn hình thông báo hiện lên

<p align="center">
    <img src="./avatar/screenshot7.png" width="640" />
</p>

Tuy nhiên, khi ấn login thì sẽ cố lỗi xảy ra dẫn tới không vào được trang chủ. Lỗi này là do bảng **TABLE_OPTIONS** không được đặt tên theo đúng quy tắc.
Để sửa lỗi này, mở phpMyAdmin và chọn vào tên cơ sở dữ liệu đã ghi ở bước trước đó. Chọn mục **SQL** ở thanh điều hướng phía trên và ghi câu lệnh sau:

    ALTER TABLE TABLE_OPTIONS RENAME TO NP_OPTIONS

Và sau khi bảng này đã hoàn thiện thì có thể đăng nhập bình thường 

<p align="center">
    <img src="./avatar/screenshot8.png" width="640" />
</p>

# [**Controller**](#controller)

Dưới đây là những controller quan trọng có lẽ không nên xóa đi

**CHANGE PASSWORD**: thay đổi mật khẩu

**CRON**: cào dữ liệu từ các trang khác

**INDEX**: xử lý ở trang chủ

**LOGIN**: đăng nhập

**LOGOUT**: đăng xuất

**PASSWORD RESET**: khôi phục mật khẩu

**PROFILE**: xử lý thông tin cá nhân khi đăng nhập 

**RECOVERY**: khôi phục tài khoản

**SETTING**: cài đặt trang 

**SIGN UP**: đăng kí mới tài khoản

**USERS**: quản lý người dùng
 
# [**Made with 💘 and PHP <img src="https://www.vectorlogo.zone/logos/php/php-horizontal.svg" width="60">**](#made-with-love-and-php)