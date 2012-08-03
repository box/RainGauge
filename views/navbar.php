<div class="navbar navbar-fixed-top">
  <div class="navbar-inner">
    <div class="container">
      <a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse">
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
        <span class="icon-bar"></span>
      </a>
      <a class="brand" href="<?php echo site_url(); ?>"><img src="img/raingauge_drops_small.png">Box Rain Gauge</a> 
      <div class="nav-collapse">
        <ul class="nav">
          <li class="dropdown">
              <a href="#" class="dropdown-toggle" data-toggle="dropdown">Servers <b class="caret"></b></a>
              <ul class="dropdown-menu">
                <?php foreach ($servers as $s) { ?>
                  <li><a href="<?php echo site_url()."?action=server&server=".urlencode($s); ?>"><?php echo $s; ?></a></li>
                <?php } ?>
              </ul>
            </li>
      </div>
    </div>
  </div>
</div>
