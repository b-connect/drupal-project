require 'yaml'

# The absolute path to the root directory of the project. Both Drupal VM and
# the config file need to be contained within this path.
ENV['DRUPALVM_PROJECT_ROOT'] = "#{__dir__}"
# The relative path from the project root to the config directory where you
# placed your config.yml file.
ENV['DRUPALVM_CONFIG_DIR'] = "config"

ENV['DRUPALVM_ENV'] = "local"

DEV_SERVER_CONFIG = "#{ENV['DRUPALVM_PROJECT_ROOT']}/#{ENV['DRUPALVM_PROJECT_ROOT']}/dev.server.config.yml"
STAGE_SERVER_CONFIG = "#{ENV['DRUPALVM_PROJECT_ROOT']}/#{ENV['DRUPALVM_PROJECT_ROOT']}/stage.server.config.yml"

if ENV['DRUPALVM_ENV'] == 'dev'
  if File.exists?(DEV_SERVER_CONFIG)
    config = YAML.load_file(DEV_SERVER_CONFIG)
  end
end

if ENV['DRUPALVM_ENV'] == 'stage'
  if File.exists?(STAGE_SERVER_CONFIG)
    config = YAML.load_file(STAGE_SERVER_CONFIG)
  end
end



# The relative path from the project root to the directory where Drupal VM is located.
# If you're using composer:
ENV['DRUPALVM_DIR'] = "vendor/geerlingguy/drupal-vm"

# Load the real Vagrantfile
load "#{__dir__}/#{ENV['DRUPALVM_DIR']}/Vagrantfile"
