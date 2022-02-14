@servers(['local' => '127.0.0.1', 'staging' => ['root@140.82.0.203','root@207.148.31.121']])

@setup
    // the repository to clone
    // $repo = 'git@github.com:ahaneef29/php-shop.git';
    $repo = 'https://github.com/ahaneef29/Nordic-Store.git';

    // the branch to clone
    $branch = 'master';

    // set up timezones
    date_default_timezone_set('Asia/Dubai');
    
    // we want the releases to be timestamps to ensure uniqueness
    $date = date('YmdHis');

    // the application directory on your server
    $appDir = '/var/www/html';

    // this is where the releases will be stored
    $buildsDir = $appDir . '/releases';

    // this is where the deployment will be
    $deploymentDir = $buildsDir . '/' . $date;

    // and this is the document root directory
    $serve = $appDir . '/current';

@endsetup

@task('dir')
    echo "Preparing new deployment directory..."
    
    mkdir -p {{ $buildsDir }}
    cd {{ $buildsDir }}
    mkdir -p {{ $date }}
    
    echo "Preparing new deployment directory complete."
@endtask

@task('git')
    echo "Cloning repository..."
    
    cd {{ $deploymentDir }}
    git clone --depth 1 -b {{ $branch }} "{{ $repo }}" {{ $deploymentDir }}

    echo "Cloning repository complete."
@endtask

@task('install')
    echo "Installing dependencies...";

    {{-- composer install --prefer-dist
    cp ../../wp-config.php ./wp-config.php --}}
    
    echo "Installing dependencies complete."
@endtask

@task('live')
    echo "Creating symlinks for the live version..."
    
    cd {{ $deploymentDir }}
    ln -nfs {{ $deploymentDir }} {{ $serve }}
    {{-- ln -nfs {{ $appDir }}/uploads {{ $serve }}/wp-content/ --}}
    
    echo "Creating symlinks completed."
@endtask

@task('rollback')
    echo ">>". $buildsDir;
    cd {{ $buildsDir }}
    ln -nfs {{ $buildsDir }}/$(find . -maxdepth 1 -name "20*" | sort  | tail -n 2 | head -n1) {{ $serve }}
    echo "Rolled back to $(find . -maxdepth 1 -name "20*" | sort  | tail -n 2 | head -n1)"
@endtask

@task('deployment_cleanup')
    echo "Cleaning up old deployments..."
	
    cd {{ $buildsDir }}
    ls -t | tail -n +4 | xargs rm -rf
    
	echo "Cleaned up old deployments."
@endtask

@story('rollback', ['on' => 'staging'])
    rollback
@endstory

@story('deploy-staging', ['on' => 'staging'])
    dir
    git
    {{-- install --}}
    live
    deployment_cleanup
@endstory

{{-- @story('deploy-production', ['on' => 'production'])
    dir
    git
    install
    live
    deployment_cleanup
@endstory --}}