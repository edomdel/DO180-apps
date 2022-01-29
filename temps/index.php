#!/usr/bin/bash
#
# Copyright 2021 Red Hat, Inc.
#
# NAME
#     lab-multicontainer-openshift - start/finish script for DO180
#
# SYNOPSIS
#     lab-example {start|finish}
#
#        start  - configures the environment at the start of a lab or exercise.
#        finish - executes any administrative tasks after completion of a lab or exercise.
#
#     All functions only work on workstation
#
# DESCRIPTION
#     This script configures GE: Creating an Application on OpenShift
#
# CHANGELOG
#   * Mon Apr 26 2021 Harpal Singh <harpasin@redhat.com>
#   - Initial code.

PATH=/usr/bin:/bin:/usr/sbin:/sbin

# Initialize and set some variables
run_as_root='true'
this="multicontainer-application"
title="Guided Exercise: Creating an Application on OpenShift"
target='workstation'

# This defines which subcommands are supported (solve, reset, etc.).
# Corresponding lab_COMMAND functions must be defined.
declare -a valid_commands=(start finish)

# Additional functions for this grading script

function print_usage {
  local problem_name=$(basename $0 | sed -e 's/^lab-//')
  cat << EOF
This script controls the start and completion of this lab.
Usage: lab ${problem_name} COMMAND
       lab ${problem_name} -h|--help

COMMAND is one of: ${valid_commands[@]}

EOF
}

function lab_start {
  print_header "Setting up ${target} for the ${title}"

  check_podman_registry_config

  print_line

  ocp4_is_cluster_up

  pad " · Ensure application project does not exist"
  delete_project ${RHT_OCP4_DEV_USER}-application
  success_if_equal $? 0

  grab_lab_files
  print_line
}

function lab_finish {
  print_header "Completing the ${title}"

  ocp4_login_as_developer
  pad " · Removing ${RHT_OCP4_DEV_USER}-application project"
  if [ $? -eq 0 ]; then
    delete_project ${RHT_OCP4_DEV_USER}-application
    success_if_equal $? 0
  else
    print_FAIL
  fi

pad " · Removing the project directory"
  if remove_directory /home/student/DO180/labs/multicontainer-application; then
    print_SUCCESS
  else
    print_FAIL
  fi
  pad " · Removing the solution directory"
  if remove_directory /home/student/DO180/solutions/multicontainer-application; then
    print_SUCCESS
  else
    print_FAIL
  fi

}

############### Don't EVER change anything below this line ###############

# Source library of functions
source /usr/local/lib/labtool.shlib
source /usr/local/lib/labtool.do180.shlib

grading_main_program "$@"
#!/usr/bin/bash
#
# Copyright 2021 Red Hat, Inc.
#
# NAME
#     lab-multicontainer-design - grading/setup script for DO180
#
# SYNOPSIS
#     lab-example {start|finish}
#
#        start   - configures the environment at the start of a lab or exercise.
#        finish  - executes any administrative tasks after completion of a lab or exercise.
#
#     All functions only work on workstation
#
# DESCRIPTION
#     This script configures GE: Linking the Web Application, and MySQL Containers
#
# CHANGELOG
#   * Tue Mar 29 2021 Harpal Singh <harpasin@redhat.com>
#   - Changed functions to stop, rm, rmi for rootless podman.
#   * Mon Dec 07 2020 Michael Phillips <miphilli@redhat.com>
#   - Removing ubi7/ubi:7.7 instead of rhel7 conatiner image
#   * Tue Oct 8 2019 Jordi Sola <jordisola@redhat.com>
#   - Moved to external image repository
#   * Thu Feb 21 2019 Jordi Sola <jordisola@redhat.com>
#   - Moved to podman. Simplified.
#   * Mon Mar 27 2017 Richard Allred <rallred@redhat.com>
#   - Initial code.

PATH=/usr/bin:/bin:/usr/sbin:/sbin

# Initialize and set some variables
run_as_root='true'
this="multicontainer-design"
title="Guided Exercise: Connecting Web Application and MySQL Container"
target='workstation'

# This defines which subcommands are supported (solve, reset, etc.).
# Corresponding lab_COMMAND functions must be defined.
declare -a valid_commands=(start finish)

# Additional functions for this grading script

function print_usage {
  local problem_name=$(basename $0 | sed -e 's/^lab-//')
  cat << EOF
This script controls the setup and grading of this lab.
Usage: lab ${problem_name} COMMAND
       lab ${problem_name} -h|--help

COMMAND is one of: ${valid_commands[@]}

EOF
}

function lab_start {
  print_header "Setting up ${target} for the ${title}"

  grab_lab_files
  chown -R student:student /home/student/DO180/labs/${this}/
  chown -R 27:27 /home/student/DO180/labs/${this}/deploy/nodejs/networked/work

}


function lab_finish {
  print_header "Cleaning up the lab for ${title}"
  for container in mysql todoapi ; do
    pad " · Stopping $container container" && podman_stop_container_rootless $container
    pad " · Removing $container container" && podman_rm_container_rootless $container
  done
  

  for image in registry.redhat.io/rhel8/mysql-80:1 registry.redhat.io/rhel8/nodejs-12:1 do180/todonodejs; do
    pad " · Removing $image image" && podman_rm_image_rootless $image
  done

pad " · Removing the project directory"
  if remove_directory /home/student/DO180/labs/multicontainer-design; then
    print_SUCCESS
  else
    print_FAIL
  fi
  pad " · Removing the solution directory"
  if remove_directory /home/student/DO180/solutions/multicontainer-design; then
    print_SUCCESS
  else
    print_FAIL
  fi

}

############### Don't EVER change anything below this line ###############

# Source library of functions
source /usr/local/lib/labtool.shlib
source /usr/local/lib/labtool.do180.shlib

grading_main_program "$@"
#!/usr/bin/bash
#
# Copyright 2021 Red Hat, Inc.
#
# NAME
#     lab-multicontainer-openshift - start/finish script for DO180
#
# SYNOPSIS
#     lab-example {start|finish}
#
#        start  - configures the environment at the start of a lab or exercise.
#        finish - executes any administrative tasks after completion of a lab or exercise.
#
#     All functions only work on workstation
#
# DESCRIPTION
#     This script configures GE: Creating an Application with a Template
#
# CHANGELOG
#   * Tue Mar 24 2021 Harpal Singh <harpasin@redhat.com>
#   - Changed functions to stop, rm, rmi for rootless podman.
#   * Tue Oct 15 2019 Jordi Sola <jordisola@redhat.com>
#   - Update to shared cluster and Quay.io
#   * Thu Wed 28 2019 Dan Kolepp <dkolepp@redhat.com>
#   - Conversion to start|finish verbs
#   - changed name of script to conform to file naming standards.
#   * Mon Apr 3 2017 Richard Allred <rallred@redhat.com>
#   - Initial code.

PATH=/usr/bin:/bin:/usr/sbin:/sbin

# Initialize and set some variables
run_as_root='true'
this="multicontainer-openshift"
title="Guided Exercise: Creating an Application with a Template"
target='workstation'

# This defines which subcommands are supported (solve, reset, etc.).
# Corresponding lab_COMMAND functions must be defined.
declare -a valid_commands=(start finish)

# Additional functions for this grading script

function print_usage {
  local problem_name=$(basename $0 | sed -e 's/^lab-//')
  cat << EOF
This script controls the start and completion of this lab.
Usage: lab ${problem_name} COMMAND
       lab ${problem_name} -h|--help

COMMAND is one of: ${valid_commands[@]}

EOF
}

function lab_start {
  print_header "Setting up ${target} for the ${title}"

  check_podman_registry_config

  print_line

  ocp4_is_cluster_up

  pad " · Ensure template project does not exist"
  delete_project ${RHT_OCP4_DEV_USER}-template
  success_if_equal $? 0

  grab_lab_files
  print_line
}

function lab_finish {
  print_header "Completing the ${title}"

  ocp4_login_as_developer
  pad " · Removing ${RHT_OCP4_DEV_USER}-template project"
  if [ $? -eq 0 ]; then
    delete_project ${RHT_OCP4_DEV_USER}-template
    success_if_equal $? 0
  else
    print_FAIL
  fi

  pad " · Removing the project directory"
  if remove_directory /home/student/DO180/labs/multicontainer-openshift; then
    print_SUCCESS
  else
    print_FAIL
  fi
  pad " · Removing the solution directory"
  if remove_directory /home/student/DO180/solutions/multicontainer-openshift; then
    print_SUCCESS
  else
    print_FAIL
  fi
}

############### Don't EVER change anything below this line ###############

# Source library of functions
source /usr/local/lib/labtool.shlib
source /usr/local/lib/labtool.do180.shlib

grading_main_program "$@"
#!/usr/bin/bash
#
# Copyright 2021 Red Hat, Inc.
#
# NAME
#     lab-multicontainer-review - grading/setup script for DO180
#
# SYNOPSIS
#     lab-example {setup|grade|reset}
#
#        start   - configures the environment at the start of a lab or exercise.
#        grade   - checks that containers and images have been created successfully.
#        finish  - executes any administrative tasks after completion of a lab or exercise.
#
#     All functions only work on workstation
#
# DESCRIPTION
#     This script configures Lab: Deploying Multi-container Applications
#
# CHANGELOG
#   * Tue Mar 30 2021 Harpal Singh <harpasin@redhat.com>
#   - Changed functions to remove images for rootless podman.
#   * Fri Feb 22 2019 Eduardo Ramirez <eramirez@redhat.com>
#   - Update to OCP 4.0
#   * Mon Apr 8 2017 Richard Allred <rallred@redhat.com>
#   - Initial code.

PATH=/usr/bin:/bin:/usr/sbin:/sbin

# Initialize and set some variables
run_as_root='true'
this="multicontainer-review"
title="Lab: Deploying Multi-container Applications"
target='workstation'

# This defines which subcommands are supported (solve, reset, etc.).
# Corresponding lab_COMMAND functions must be defined.
declare -a valid_commands=(start grade finish)

# Additional functions for this grading script

function print_usage {
  local problem_name=$(basename $0 | sed -e 's/^lab-//')
  cat << EOF
This script controls the setup and grading of this lab.
Usage: lab ${problem_name} COMMAND
       lab ${problem_name} -h|--help

COMMAND is one of: ${valid_commands[@]}

EOF
}

function lab_start {
  print_header "Setting up ${target} for the ${title}"

  grab_lab_files true

  ocp4_is_cluster_up

}

function lab_grade {
  print_header "Grading the student's work for ${title}"

  pad '• Accessing Quotes web application'
  local fqdn=quote-php-${RHT_OCP4_DEV_USER}-deploy.${RHT_OCP4_WILDCARD_DOMAIN}
  echo "App route FQDN is ${fqdn}"

  curl -f -s --connect-timeout 1 "http://${fqdn}"
  if [ $? == "0" ]; then
    print_PASS
  else
    print_FAIL
    print_line "   --> Could not access route at: $fqdn"
  fi

  print_line
}

function find_router_port {
  local stmt=$(${oc} set env dc/router -n default --list | grep HTTP_PORT)
  IFS='=' read -ra ROUTER <<< "$stmt"
  echo "${ROUTER[1]}"
}

function find_route_fqdn {
  #oc login -u developer &>/dev/null
  #oc project deploy &>/dev/null
  local stmt=$(${oc} describe route quote-php | grep Host)
  IFS=':' read -ra FQDN <<< "$stmt"
  echo "${FQDN[1]}" | tr -d [:space:]
}

function lab_finish {
  print_header "Completing the ${title}"

  ocp4_login_as_developer

  pad " · Deleting the ${RHT_OCP4_DEV_USER}-deploy project"
  if [ $? -eq 0 ]; then
    delete_project ${RHT_OCP4_DEV_USER}-deploy
    success_if_equal $? 0
  else
    print_FAIL
  fi

  print_line " · Remove local images"
  for image in localhost/do180-mysql-80-rhel8 \
               localhost/do180-quote-php \
               quay.io/${RHT_OCP4_QUAY_USER}/do180-mysql-80-rhel8 \
               quay.io/${RHT_OCP4_QUAY_USER}/do180-todonodejs \
               registry.access.redhat.com/ubi8/ubi \
               registry.redhat.io/rhel8/mysql-80:1 ; do

    pad " · Removing $image image" && podman_rm_image_rootless $image  
  done
  
  pad " · Removing the project directory"
  if remove_directory /home/student/DO180/labs/multicontainer-review; then
    print_SUCCESS
  else
    print_FAIL
  fi
  pad " · Removing the solution directory"
  if remove_directory /home/student/DO180/solutions/multicontainer-review; then
    print_SUCCESS
  else
    print_FAIL
  fi

}

############### Don't EVER change anything below this line ###############

# Source library of functions
source /usr/local/lib/labtool.shlib
source /usr/local/lib/labtool.do180.shlib

grading_main_program "$@"
