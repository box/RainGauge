class profile::base {
  $user = 'vagrant'
  user { $user:
    ensure => present
  }

  file { "/home/${user}":
    ensure => directory,
    owner  => $user,
    mode   => "0750"
  }

  file { "/home/${profile::base::user}/.bashrc":
    ensure => present,
    owner  => $profile::base::user,
    mode   => "0644",
    source => 'puppet:///modules/profile/bashrc',
  }

  file { '/root/.ssh':
    ensure => directory,
    owner => 'root',
    mode => '700'
  }

  file { '/root/.ssh/authorized_keys':
    ensure => present,
    owner => 'root',
    mode => '600',
    source => 'puppet:///modules/profile/id_rsa.pub'
  }

  file { '/root/.ssh/id_rsa':
    ensure => present,
    owner => 'root',
    mode => '600',
    source => 'puppet:///modules/profile/id_rsa'
  }

  file { "/home/${profile::base::user}/.my.cnf":
    ensure => present,
    owner  => $profile::base::user,
    mode   => "0600",
    content => "[client]
user=dba
password=qwerty
"
  }

  file { "/root/.my.cnf":
    ensure => present,
    owner  => 'root',
    mode   => "0600",
    content => "[client]
user=dba
password=qwerty
"
  }

  yumrepo { 'Percona':
    baseurl => 'http://repo.percona.com/centos/$releasever/os/$basearch/',
    enabled => 1,
    gpgcheck => 0,
    descr => 'Percona',
    retries => 3
  }

  $packages = [ 'vim-enhanced',
    'Percona-Server-client-56', 'Percona-Server-server-56',
    'Percona-Server-devel-56', 'Percona-Server-shared-56',
    'percona-toolkit',
    'httpd', 'php', 'php-mysql' ]

  package { $packages:
    ensure => installed,
    require => [Yumrepo['Percona']]
  }

  service { 'mysql':
    ensure => running,
    enable => true,
    require => Package['Percona-Server-server-56']
  }

  file { "/home/${profile::base::user}/mysql_grants.sql":
    ensure => present,
    owner  => $profile::base::user,
    mode   => "0400",
    source => 'puppet:///modules/profile/mysql_grants.sql',
  }

  exec { 'Create MySQL users':
    path    => '/usr/bin:/usr/sbin',
    user    => $profile::base::user,
    command => "mysql -u root < /home/${$profile::base::user}/mysql_grants.sql",
    require => [ Service['mysql'], File["/home/${profile::base::user}/mysql_grants.sql"] ],
    before => File["/home/${profile::base::user}/.my.cnf"],
    unless => 'mysql -e "SHOW GRANTS FOR dba@localhost"'
  }

  file { '/etc/my.cnf':
    ensure  => present,
    owner   => 'mysql',
    source  => 'puppet:///modules/profile/my-master.cnf',
    require => Package['Percona-Server-server-56'],
    notify  => Service['mysql']
  }

  service { 'httpd':
    ensure => running,
    enable => true,
    require => Package['httpd']
  }

  # RainGauge files

  file { '/var/www/html/RainGauge':
    ensure => link,
    target => '/RainGauge',
    require => Package['httpd']

  }

  file { '/usr/bin/raingauge_package_and_send.sh':
    ensure => present,
    owner  => 'root',
    mode   => "0755",
    source => '/RainGauge/scripts/raingauge_package_and_send.sh'
  }

  file { '/usr/bin/pt-stalk-raingauge':
    ensure => present,
    owner  => 'root',
    mode   => "0755",
    source => '/RainGauge/scripts/pt-stalk-raingauge'
  }

  file { '/usr/bin/raingauge_triggers.sh':
    ensure => present,
    owner  => 'root',
    mode   => "0755",
    source => '/RainGauge/scripts/raingauge_triggers.sh'
  }

  file { '/etc/raingauge_rc':
    ensure => present,
    owner  => 'root',
    mode   => "0644",
    source => '/RainGauge/scripts/raingauge_rc'
  }

  file { '/etc/init.d/raingauge':
    ensure => present,
    owner  => 'root',
    mode   => "0755",
    source => '/RainGauge/scripts/raingauge_service'
  }

  service { 'raingauge':
    ensure => running,
    require => [
      File['/etc/init.d/raingauge'],
      File['/usr/bin/pt-stalk-raingauge'],
      Exec['Create MySQL users']
    ]

  }

  cron { 'raingauge':
    command => '[[ -d /www/RainGauge/collected/ ]] && find /www/RainGauge/collected/ -mindepth 1 -mtime +2 -exec rm -rf {} \;',
    user    => 'root',
    hour    => 0,
    minute  => 0,
  }

}
