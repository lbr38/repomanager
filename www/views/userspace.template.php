<section class="main">

    <h3>USERSPACE</h3>

    <div class="div-flex div-generic-blue">
        <div class="flex-div-100">
            <table class="table-generic table-small">
                <tr>
                    <td>LOGIN</td>
                    <td><?= $_SESSION['username'] ?></td>
                </tr>
                <tr>
                    <td>ROLE</td>
                    <td><?= $_SESSION['role'] ?></td>
                </tr>
            </table>
        </div>
    </div>

    <div class="div-flex">
        <div class="flex-div-50">
            <h4>PERSONAL INFORMATIONS</h4>

            <div class="div-generic-blue">
                <form action="/userspace" method="post" autocomplete="off">
                    <input type="hidden" name="action" value="editPersonnalInfos" />
                    <p>First name:</p>
                    <input type="text" class="input-large" name="first_name" value="<?php echo !empty($_SESSION['first_name']) ? $_SESSION['first_name'] : ''; ?>">
                    <br><br>
                    <p>Last name:</p>
                    <input type="text" class="input-large" name="last_name" value="<?php echo !empty($_SESSION['last_name']) ? $_SESSION['last_name'] : ''; ?>">
                    <br><br>
                    <p>Email:</p>
                    <input type="email" class="input-large" name="email" value="<?php echo !empty($_SESSION['email']) ? $_SESSION['email'] : ''; ?>">
                    <br><br>
                    <button class="btn-medium-green">Save</button>
                </form>
            </div>
        </div>

        <div class="flex-div-50">
            <h4>CHANGE PASSWORD</h4>
            
            <div class="div-generic-blue">
                <form action="/userspace" method="post" autocomplete="off">
                    <input type="hidden" name="action" value="changePassword" />
                    <p>Current password:</p>
                    <input type="password" class="input-large" name="actual_password" required />
                    <br><br>
                    <p>New password:</p>
                    <input type="password" class="input-large" name="new_password" required />
                    <br><br>
                    <p>New password (re-type) :</p>
                    <input type="password" class="input-large" name="new_password_retype" required />
                    <br><br>
                    <button class="btn-medium-green">Save</button>
                </form>
            </div>
        </div>
    </div>
</section>