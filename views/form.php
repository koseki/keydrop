<?php
if (!empty($error)) {
    echo "<p><strong>$error</strong></p>";
}
?>
<p>Submit your password. We will add your account soon.</p>
<form method="post" action="" autocomplete="off">
  ID: <input name="username" type="text">
  PASS: <input name="password" type="text">
  <input type="submit" name="submit" value="submit">
</form>

<hr>

<p>Password must have:</p>
<ul>
  <li>More than 10 characters</li>
  <li>At least 1 numeric character (<code>0 - 9</code>)</li>
  <li>At least 1 capital character (<code>A - Z</code>)</li>
  <li>At lease 1 symbol character (<code>#$%@&amp;*!...</code>)</li>
</ul>
