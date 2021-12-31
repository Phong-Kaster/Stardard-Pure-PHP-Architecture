        <form action="install.php" id="controls" class="step">
            <div class="form-errors color-red"></div>

            <div class="inner-wrapper">
                <div class="subsection">
                    <div class="section-title">License:</div>
                    
                    <div class="clearfix mb-20">
                        <div class="col s12 m6 l6 mb-10">
                            <label class="form-label">Purchase Code</label>
                            <div class="input-tip">
                                Please include your purchase code.
                            </div>
                        </div>

                        <div class="col s12 m6 m-last l6 l-last">
                            <input type="text" class="input required" name="key" value="prowebber">
                        </div>
                    </div>
                </div>

                <div class="subsection">
                    <div class="section-title">Upgrade:</div>
                    
                    <div class="clearfix mb-10">
                        <div class="col s12 m6 l6 mb-10">
                            <label class="form-label">Are you upgrading the app?</label>
                            <div class="input-tip">
                                If you're upgrading the app, please select the current installed version
                                from the dropdown list. <br><br>
                                Please read <a href="https://codecanyon.net/item/nextpost-auto-post-schedule-manage-your-instagram-multi-accounts-php-script/19456996" target="_blank">release notes</a> 
                                and <a href="http://docs.getnextpost.io" target="_blank">upgrade instruction</a> before upgrading!
                            </div>
                        </div>

                        <div class="col s12 m6 m-last l6 l-last">
                            <select name="upgrade" class="input">
                                <option value="">No, do clean install</option>
                                <option value="1.0">Yes, upgrade from version 1.0</option>
                                <option value="2.0">Yes, upgrade from version 2.0</option>
                                <option value="3.0">Yes, upgrade from version 3.0 or 3.0.X</option>
                            </select>
                        </div>
                    </div>

                    <div class="clearfix mb-20 none upgrade-only">
                        <div class="col s12 m6 l6 mb-10">
                            <label class="form-label">Current Crypto Key</label>
                            <div class="input-tip">
                                Include crypto key value from /app/config.php in your backup
                            </div>
                        </div>

                        <div class="col s12 m6 m-last l6 l-last">
                            <input type="text" class="input required" name="crypto-key" value="" disabled>
                        </div>
                    </div>
                </div>

                <div class="subsection">
                    <div class="section-title">Database connection details:</div>
                    
                    <div class="clearfix mb-20">
                        <div class="col s12 m6 l6 mb-10">
                            <label class="form-label">Database Host</label>
                            <div class="input-tip">
                                You should be able to get this info from your 
                                web host, if localhost doesn't work
                            </div>
                        </div>

                        <div class="col s12 m6 m-last l6 l-last">
                            <input type="text" class="input required" name="db-host" value="localhost">
                        </div>
                    </div>

                    <div class="clearfix mb-20">
                        <div class="col s12 m6 l6 mb-10">
                            <label class="form-label">Database Name</label>
                            <div class="input-tip">
                                The name of the database you want to install NextPost in
                            </div>
                        </div>

                        <div class="col s12 m6 m-last l6 l-last">
                            <input type="text" class="input required" name="db-name" value="">
                        </div>
                    </div>

                    <div class="clearfix mb-20">
                        <div class="col s12 m6 l6 mb-10">
                            <label class="form-label">Username</label>
                            <div class="input-tip">
                                Your MySQL username
                            </div>
                        </div>

                        <div class="col s12 m6 m-last l6 l-last">
                            <input type="text" class="input required" name="db-username" value="">
                        </div>
                    </div>

                    <div class="clearfix mb-20">
                        <div class="col s12 m6 l6 mb-10">
                            <label class="form-label">Password</label>
                            <div class="input-tip">
                                Your MySQL password
                            </div>
                        </div>

                        <div class="col s12 m6 m-last l6 l-last">
                            <input type="password" class="input" name="db-password" value="">
                        </div>
                    </div>

                    <div class="clearfix mb-20">
                        <div class="col s12 m6 l6 mb-10">
                            <label class="form-label">Table Prefix</label>
                            <div class="input-tip">
                                If you want to run multiple installation in a single database,
                                change this
                            </div>
                        </div>

                        <div class="col s12 m6 m-last l6 l-last">
                            <input type="text" class="input" name="db-table-prefix" value="np_">
                        </div>
                    </div>
                </div>
                
                <div class="subsection mb-0 install-only">
                    <div class="section-title">Administrative account details:</div>
                    
                    <div class="clearfix">
                        <div class="col s12 m6 l6 mb-20">
                            <label class="form-label">Firstname</label>
                            <input type="text" class="input required" name="user-firstname" value="">
                        </div>

                        <div class="col s12 m6 m-last l6 l-last mb-20">
                            <label class="form-label">Lastname</label>
                            <input type="text" class="input" name="user-lastname" value="">
                        </div>
                    </div>

                    <div class="clearfix">
                        <div class="col s12 m6 l6 mb-20">
                            <label class="form-label">Email</label>
                            <input type="text" class="input required" name="user-email" value="">
                        </div>

                        <div class="col s12 m6 m-last l6 l-last mb-20">
                            <label class="form-label">Password</label>
                            <input type="password" class="input required" name="user-password" value="">
                        </div>
                    </div>

                    <div class="clearfix">
                        <div class="col s12 m6 l6 mb-20">
                            <label class="form-label">Time Zone</label>
                            <select name="user-timezone" class="input required">
                                <?php foreach (getTimezones() as $k => $v): ?>
                                    <option value="<?= $k ?>" <?= $k == "UTC" ? "selected" : "" ?>><?= $v ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="gotonext mt-40">
                <div class="clearfix">
                    <div class="col s12 m6 offset-m3 m-last l4 offset-l4 l-last">
                        <input type="submit" value="Finish Installation" class="oval fluid button">
                    </div>
                </div>
            </div>
        </form>