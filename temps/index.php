#!/usr/bin/bash
#
# Copyright 2021 Red Hat, Inc.
#
# NAME
#     lab-troubleshoot-container - grading/setup script for DO180
#
# SYNOPSIS
#     lab-basic-apache {setup|grade|reset}
#
#        start   - configures the environment at the start of a lab or exercise.
#        finish  - executes any administrative tasks after completion of a lab or exercise.
#
#     All functions only work on workstation
#
# DESCRIPTION
#     This script is for the GE 'Configuring Apache Container Logs for Debugging'
#
# CHANGELOG
#   * Tue Mar 31 2021 Harpal Singh <harpasin@redhat.com>
#   - Changed functions to stop, rm, rmi for rootless podman.
#   * Tue Mar 05 2019 Eduardo Ramirez <eramirez@redhat.com>
#   - Update to OCP 4.0
#
#   * Wed Apr 11 2017 Ravi Srinivasan <ravis@redhat.com>
#   - Updated for DO180
#   - Removed solve verb
#
#   * Sun Jan 10 2016 Zach Gutterman <zgutterm@redhat.com>
#   - original code from DO276

PATH=/usr/bin:/bin:/usr/sbin:/sbin:/usr/local/bin:/usr/local/sbin

# Initialize and set some variables
run_as_root='true'
this='troubleshoot-container'
target='workstation'
title='Guided Excercise: Configuring Apache Container Logs for Debugging'

# This defines which subcommands are supported (solve, reset, etc.).
# Corresponding lab_COMMAND functions must be defined.
declare -a valid_commands=(start finish)

# Additional functions for this grading script

function print_usage {
  local problem_name=$(basename $0 | sed -e 's/^lab-//')
  cat << EOF
This script controls the setup and reset of this lab.
Usage: lab ${problem_name} COMMAND
       lab ${problem_name} -h|--help

COMMAND is one of: ${valid_commands[@]}

EOF
}

function lab_start {
  print_header "Setting up ${target} for the ${title}"

  check_podman_registry_config

  grab_lab_files
}

function lab_finish {
  print_header "Completing the ${title}"

  pad " · Stopping the troubleshoot-container container"
  podman_stop_container_rootless troubleshoot-container

  pad " · Removing the troubleshoot-container container"
  podman_rm_container_rootless troubleshoot-container

  pad " · Removing the troubleshoot-container container image"
  podman_rm_image_rootless troubleshoot-container

  pad " · Removing redhattraining/httpd-parent container image"
  podman_rm_image_rootless httpd-parent
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
#     lab-troubleshoot-review - lab script for DO180 for Ch08 Lab.
#
# SYNOPSIS
#     lab-troubleshoot-review {start|grade|finish}
#
#        start   - configures the environment at the start of a lab or exercise.
#        grade   - checks that lab tasks are correctly executed.
#        finish  - executes any administrative tasks after completion of a lab or exercise.
#
#     All functions only work on workstation
#
# DESCRIPTION
#     This script configures Ch08 Final Lab Troubleshooting Containerized Applications
#
# CHANGELOG
#   * Wed Jun 12 2019 Michael Jarrett <mjarrett@redhat.com>
#   - updated application route
#   * Thu Feb 28 2018 Dan Kolepp <dkolepp@redhat.com>
#   - changed lab name from lab-troubleshooting-lab to
#     lab-troubleshoot-review, to match DocBook standards.
#   - Updated verbs from setup/grade/cleanup, to start/grade/finish
#   * Thu Jun 2018 Razique Mahroua <rmahroua@redhat.com>
#   - updates and cleans the code
#   * Thu Apr 20 2017 Ricardo Jun <jtaniguc@redhat.com>
#   - original code

PATH=/usr/bin:/bin:/usr/sbin:/sbin

# Initialize and set some variables
run_as_root='true'
this='troubleshoot-review'
target='workstation'
title='Lab: Troubleshooting Containerized Applications'
project_name=do180-apps


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
  ocp4_print_prereq_header
  ocp4_verify_local_clone_exist
  ocp4_verify_prereq_git_projects 'nodejs-app'
  ocp4_fail_if_project_exists "${RHT_OCP4_DEV_USER}-nodejs-app"
  ocp4_fail_if_container_exists 'test'
  ocp4_is_cluster_up

  ocp4_print_setup_header
  grab_lab_files
  ocp4_print_setup_footer
}

function lab_grade {
  print_header "Grading the student's work for the ${title}"

  pad " · The nodejs-dev application response is correct"
  local app_url="nodejs-dev-${RHT_OCP4_DEV_USER}-nodejs-app.${RHT_OCP4_WILDCARD_DOMAIN}"
  curl -f $app_url
  if [ $? -eq 0 ]; then
    curl $app_url | grep "Hello World from pod: nodejs-dev"
    if [ $? -eq 0 ]; then
      print_PASS
    else
      print_FAIL
      print_line "Hello World response not received;"
      print_line "Instead, received the following response from $app_url: "
      print_line "$(curl $app_url)"
    fi
  else
    print_FAIL
    print_line "Unable to access the application URL: $app_url"
  fi

  print_line
}



function lab_finish {
  print_header "Completing the ${title}"

  ocp4_login_as_developer
  pad " · Deleting the '${RHT_OCP4_DEV_USER}-nodejs-app' OpenShift project"
  if [ $? -eq 0 ]; then
    delete_project "${RHT_OCP4_DEV_USER}-nodejs-app"
    success_if_equal $? 0
  else
    print_FAIL
    print_line "Unable to login to OpenShift."
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
#     lab-troubleshoot-s2i - grading/setup script for DO180
#
# SYNOPSIS
#     lab-troubleshoot-s2i {start|finish}
#
#        start   - configures the environment at the start of a lab or exercise.
#        finish  - executes any administrative tasks after completion of a lab or exercise.
#
#     All functions only work on workstation
#
# DESCRIPTION
#     This script is for the GE 'Troubleshooting an OpenShift Build'
#
# CHANGELOG
#   * Wed Jun 12 2019 Michael Jarrett <mjarrett@redhat.com>
#   - updated Initialize and set some variables
#   * Tue Jun 19 2018 Razique Mahroua <rmahroua@redhat.com>
#   - updates the script to work with the 3.9 course
#   * Mon Apr 24 2017 Ricardo Jun <jtaniguc@redhat.com>
#   - original code

PATH=/usr/bin:/bin:/usr/sbin:/sbin:/usr/local/bin:/usr/local/sbin

# Initialize and set some variables
run_as_root='true'
this='troubleshoot-s2i'
target='workstation'
title='Guided Exercise: Troubleshooting an OpenShift Build'

# This defines which subcommands are supported (solve, reset, etc.).
# Corresponding lab_COMMAND functions must be defined.
declare -a valid_commands=(start finish)

project_name=DO180-apps

# Additional functions for this grading script

function print_usage {
  local problem_name=$(basename $0 | sed -e 's/^lab-//')
  cat << EOF
This script controls the setup and reset of this lab.
Usage: lab ${problem_name} COMMAND
       lab ${problem_name} -h|--help

COMMAND is one of: ${valid_commands[@]}

EOF
}

function lab_start {

  ocp4_print_prereq_header
  ocp4_verify_local_clone_exist
  ocp4_verify_prereq_git_projects 'nodejs-helloworld'
  ocp4_fail_if_project_exists "${RHT_OCP4_DEV_USER}-nodejs"
  ocp4_fail_if_container_exists 'test'
  ocp4_is_cluster_up

  ocp4_print_setup_header
  grab_lab_files
  ocp4_print_setup_footer

}

function lab_finish {
  print_header "Completing the ${title}"

  ocp4_login_as_developer
  pad " · Deleting the '${RHT_OCP4_DEV_USER}-nodejs' OpenShift project"
  if [ $? -eq 0 ]; then
    delete_project "${RHT_OCP4_DEV_USER}-nodejs"
    success_if_equal $? 0
  fi

}

############### Don't EVER change anything below this line ###############

# Source library of functions
source /usr/local/lib/labtool.shlib
source /usr/local/lib/labtool.do180.shlib

grading_main_program "$@"
