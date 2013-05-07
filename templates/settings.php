<form id="redmine_ex" action="#" method="post">
    <fieldset class="personalblock">
        <legend><strong>Redmine extended Authentication</strong></legend>
        <input type="hidden" name="requesttoken" value="<?php p($_['requesttoken']) ?>" id="requesttoken">
        <p>
            <label for="redmine_ex_db_host"><?php p($l->t('DB Host'));?></label>
            <input type="text" id="redmine_ex_db_host" name="redmine_ex_db_host"
                value="<?php p($_['redmine_ex_db_host']); ?>" />

            <label for="redmine_ex_db_name"><?php p($l->t('DB Name'));?></label>
            <input type="text" id="redmine_ex_db_name" name="redmine_ex_db_name" 
                value="<?php p($_['redmine_ex_db_name']); ?>" />
        </p>

        <p>
            <label for="redmine_ex_db_port"><?php p($l->t('DB Port'));?></label>
            <input type="text" id="redmine_ex_db_port" name="redmine_ex_db_port" 
                value="<?php p($_['redmine_ex_db_port']); ?>" />

            <label for="redmine_ex_db_driver"><?php p($l->t('DB Driver'));?></label>
            <?php $db_driver = array('mysql' => 'MySQL', 'pgsql' => 'PostgreSQL');?>
            <select id="redmine_ex_db_driver" name="redmine_ex_db_driver">
                <?php foreach ($db_driver as $driver => $name): ?>
                    <?php p($_['redmine_ex_db_driver']); ?>
                    <?php if ($_['redmine_ex_db_driver'] === $driver): ?>
                        <option selected="selected" value="<?php p($driver); ?>"><?php p($name); ?></option>
                    <?php else: ?>
                        <option value="<?php p($driver); ?>"><?php p($name); ?></option>
                    <?php endif ?>
                <?php endforeach; ?>
            </select>
        </p>

        <p>
            <label for="redmine_ex_db_user"><?php p($l->t('DB User'));?></label>
            <input type="text" id="redmine_ex_db_user" name="redmine_ex_db_user" 
                value="<?php p($_['redmine_ex_db_user']); ?>" />

            <label for="redmine_ex_db_password"><?php p($l->t('DB Password'));?></label>
            <input type="password" id="redmine_ex_db_password" name="redmine_ex_db_password" 
                value="<?php p($_['redmine_ex_db_password']); ?>" />
        </p>

        <input type="submit" value="Save" />
    </fieldset>
</form>
