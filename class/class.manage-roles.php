<?php
/**
 * Copyright (c) $today.year. - Eighty / 20 Results by Wicked Strong Chicks.
 * ALL RIGHTS RESERVED
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace E20R\Roles_For_PMPro;

use E20R\Roles_For_PMPro\E20R_Roles_For_PMPro as E20R_Roles_For_PMPro;
use E20R\Utilities\PMPro_Members as PMPro_Members;
use E20R\Utilities\Utilities as Utilities;

// Deny direct access to the file
if ( ! defined( 'ABSPATH' ) ) {
	wp_die( __( "Cannot access file directly", E20R_Roles_For_PMPro::plugin_slug ) );
}

// Define the Manage_Roles class
if ( ! class_exists( 'E20R\\Roles_For_PMPro\\Manage_Roles' ) ) {
	
	class Manage_Roles {
		
		const role_key = 'e20r_roles_level_';
		
		/**
		 * Instance of this class
		 *
		 * @var Manage_Roles
		 */
		static private $instance = null;
		
		/**
		 * Configure the required membership level capabilities to view/edit/manage the specified post type
		 *
		 * @param string $post_type
		 * @param int    $level_id
		 *
		 *  TODO: Implement set_required_caps_for_level() method
		 */
		public function set_required_caps_for_level( $post_type, $level_id ) {
			
			$role_name = Manage_Roles::role_key . $level_id;
			
		}
		
		/**
		 * Checks whether a specific user has a specific role assigned already
		 *
		 * @param int    $user_id   WordPress user ID
		 * @param string $role_name Name of the role to check for
		 *
		 * @return bool
		 *
		 * @access public
		 * @since  1.0
		 */
		public function has_role( $user_id, $role_name ) {
			
			$user = get_user_by( 'ID', $user_id );
			
			return in_array( $role_name, $user->roles );
		}
		
		/**
		 * Add the new membership level role to the user at checkout
		 *
		 * @action pmpro_after_checkout
		 *
		 * @param int         $user_id
		 * @param MemberOrder $order_class
		 *
		 * @since  1.0
		 * @access public
		 */
		public function update_user_role_at_checkout( $user_id, $order_class ) {
			
			$role_name = Manage_Roles::role_key . $order_class->membership_id;
			
			if ( false === $this->has_role( $user_id, $role_name ) ) {
				
				$user = get_user_by( 'ID', $user_id );
				$user->add_role( $role_name );
			}
			
			// reset the Memberships cache
			
		}
		
		/**
		 * Remove level to cancel if needed and add old levels if not already assigned
		 *
		 * @action pmpro_before_change_level
		 *
		 * @param int   $level_id           New level being changed to
		 * @param int   $user_id            User's ID
		 * @param array $old_levels         List of active levels the user belongs to (currently)
		 * @param int   $level_id_to_cancel The membership level being changed from
		 *
		 * @since  1.0
		 */
		public function before_change_membership_level( $level_id, $user_id, $old_levels, $level_id_to_cancel ) {
			
			$utils = Utilities::get_instance();
			
			// Process existing levels & fix any missing role assignments for this user
			foreach ( $old_levels as $ol ) {
				
				$role_name = Manage_Roles::role_key . $ol->id;
				
				// Remove the membership role from the user ID if assigned (backwards compatibility)
				if ( false === $this->has_role( $user_id, $role_name ) ) {
					
					// Return error message to the WP-Admin dashboard if failing to add role(s)
					if ( false === $this->add_role_to_user( $user_id, $role_name, $ol->id ) ) {
						
						$utils->add_message(
							sprintf(
								__( 'Unable to add the %1$s membership level role for user %2$d', E20R_Roles_For_PMPro::plugin_slug ),
								esc_html__( $ol->name ),
								$user_id
							),
							'warning'
						);
					}
				}
			}
			
			// Figure out if we're supposed to _NOT_ deactivate the old level(s).
			$deactivate_old_levels = apply_filters( 'pmpro_deactivate_old_levels', true );
			
			// Remove any old level roles if we're supposed to be deactviating the level
			if ( ! empty( $level_id_to_cancel ) && true === $deactivate_old_levels ) {
				
				$role_name = Manage_Roles::role_key . $level_id_to_cancel;
				
				if ( true === $this->has_role( $user_id, $role_name ) ) {
					
					if ( false === $this->remove_role_for_user( $user_id, $role_name, $level_id_to_cancel ) ) {
						
						$old_level = pmpro_getLevel( $level_id_to_cancel );
						$utils->add_message(
							sprintf(
								__( 'Unable to remove the %1$s membership level role for user ID: %2$d', E20R_Roles_For_PMPro::plugin_slug ),
								esc_html__( $old_level->name ),
								$user_id
							),
							'warning'
						);
					}
				}
			}
			
			$role_name = Manage_Roles::role_key . $level_id;
			
			// Add the new membership role if not already assigned
			if ( false === $this->has_role( $user_id, $role_name ) ) {
				
				if ( false === $this->add_role_to_user( $user_id, $role_name, $level_id ) ) {
					
					$level = pmpro_getLevel( $level_id );
					$utils->add_message(
						sprintf(
							__( 'Unable to add the %1$s membership level role for user ID: %2$d', E20R_Roles_For_PMPro::plugin_slug ),
							esc_html__( $level->name ),
							$user_id
						),
						'warning'
					);
				}
			}
		}
		
		/**
		 * Process membership level roles after the user's membership level has been changed
		 *
		 * @param int      $user_id         The WordPress user ID
		 * @param int      $level_id        The PMPro Membership Level ID the user is being changed to
		 * @param null|int $cancel_level_id The PMPro  Membership Level ID the user being cancelled
		 *
		 * @since  1.0
		 * @access public
		 *
		 */
		public function after_change_membership_level( $user_id, $level_id, $cancel_level_id = null ) {
			
			// Process membership level cancellation (if level ID = 0 (aka empty)
			if ( empty( $level_id ) || 0 === $level_id ) {
				
				if ( empty( $cancel_level_id ) ) {
					
					// Use the order table to locate the most recent membership level id
					$last_order = new \MemberOrder();
					$last_order->getLastMemberOrder( $user_id, 'success' );
					
					$level_id_to_cancel = $last_order->membership_id;
				} else {
					
					// Using the supplied cancel_level_id
					$level_id_to_cancel = $cancel_level_id;
				}
				
				// Generate the role name
				$role_name = Manage_Roles::role_key . $level_id_to_cancel;
				
				// Check if the user has the role
				if ( true === $this->has_role( $user_id, $role_name ) ) {
					
					// They do, so remove it
					if ( false === $this->remove_role_for_user( $user_id, $role_name, $level_id_to_cancel ) ) {
						
						// Oops. Something went wrong during the role removal. Notify admin.
						$utils = Utilities::get_instance();
						
						$old_level = pmpro_getLevel( $level_id_to_cancel );
						$utils->add_message(
							sprintf(
								__( 'Unable to remove the %1$s membership level role for user ID: %2$d', E20R_Roles_For_PMPro::plugin_slug ),
								esc_html__( $old_level->name ),
								$user_id
							),
							'warning'
						);
					}
				}
			}
			
			// Check if we need to add the new role to the user ID
			$role_name = Manage_Roles::role_key . $level_id;
			
			if ( false === $this->has_role( $user_id, $role_name ) ) {
				
				// We do, so do it.
				if ( false === $this->add_role_to_user( $user_id, $role_name, $level_id ) ) {
					
					// Oops, something went sideways. Let the admin know.
					$utils = Utilities::get_instance();
					
					$level = pmpro_getLevel( $level_id );
					$utils->add_message(
						sprintf(
							__( 'Unable to add the %1$s membership level role for user ID: %2$d', E20R_Roles_For_PMPro::plugin_slug ),
							esc_html__( $level->name ),
							$user_id
						),
						'warning'
					);
				}
			}
		}
		
		/**
		 * Add a role to the list of users
		 *
		 * @param string $role_name
		 * @param int    $level_id
		 *
		 * @since 1.0
		 */
		public function add_role_to_all_level_users( $role_name, $level_id ) {
			
			$user_ids = PMPro_Members::get_members( $level_id, 'active' );
			
			if ( WP_DEBUG ) {
				error_log("Found " . count( $user_ids ) . " users to process");
			}
			
			foreach ( $user_ids as $user_id ) {
				
				$user = get_user_by( 'ID', $user_id );
				do_action( 'e20r_roles_add_level_role', $role_name, $level_id, $user );
				
				if ( false === user_can( $user, $role_name) ) {
					
					$user->add_role( $role_name );
					
					if ( WP_DEBUG ) {
						error_log("Added {$role_name} to {$user_id}");
					}
					
					$user = null;
				}
			}
		}
		
		/**
		 * Remove the specified role name from all users of that level
		 *
		 * @param  string $role_name
		 * @param  int    $level_id
		 * @param  string $status
		 *
		 * @since 1.0
		 */
		public function delete_role_from_all_level_users( $role_name, $level_id, $status = 'active' ) {
			
			$user_ids = PMPro_Members::get_members( $level_id, $status );
			
			foreach ( $user_ids as $user_id ) {
				
				$user = get_user_by( 'ID', $user_id );
				$user->remove_role( $role_name );
				
				do_action( 'e20r_roles_delete_level_role', $role_name, $level_id, $status, $user );
				
				$user = null;
			}
		}
		
		/**
		 * Adds the specified role name to the user if they're active in the specified membership level
		 *
		 * @param int    $user_id
		 * @param string $role_name
		 * @param int    $level_id
		 *
		 * @return bool
		 *
		 * @since 1.0
		 */
		public function add_role_to_user( $user_id, $role_name, $level_id ) {
			
			// Add the specified role name to the user if they're in the corresponding membership level
			if ( true === PMPro_Members::is_user( $user_id, 'active', $level_id ) ) {
				
				$user = get_user_by( 'ID', $user_id );
				$user->add_role( $role_name );
				
				do_action( 'e20r_roles_add_level_role',$role_name, $level_id, $user );
				
				return true;
			} else {
				// Warn the admin
				$utils = Utilities::get_instance();
				$utils->add_message( sprintf( __( "Error: Cannot add %s role to level %d", E20R_Roles_For_PMPro::plugin_slug ), $role_name, $level_id ), 'error' );
			}
			
			return false;
		}
		
		/**
		 * Remove the specified role from the specified user ID
		 *
		 * @param int    $user_id
		 * @param string $role_name
		 * @param null   $level_id
		 * @param bool   $force
		 *
		 * @return bool
		 */
		public function remove_role_for_user( $user_id, $role_name, $level_id = null, $force = false ) {
			
			// Get the most recent level ID
			if ( empty( $level_id ) && false === $force ) {
				
				$last_order = new \MemberOrder();
				$last_order->getLastMemberOrder( $user_id, 'cancelled', $level_id );
				$level_id = $last_order->membership_id;
				
			} else if ( empty( $level_id ) ) {
				
				$level    = pmpro_getMembershipLevelForUser( $user_id );
				$level_id = $level->id;
			}
			
			$cancel_statuses = array(
				'admin_cancelled',
				'cancelled',
				'expired',
			);
			
			if ( true === PMPro_Members::is_user( $user_id, $cancel_statuses, $level_id ) ) {
				$user = get_user_by( 'ID', $user_id );
				$user->remove_role( $role_name );
				
				do_action( 'e20r_roles_delete_level_role', $role_name, $level_id, 'active', $user );
				return true;
			}
			
			return false;
		}
		
		/**
		 * Return an instance of the current class (Manage_Roles)
		 *
		 * @return Manage_Roles
		 */
		static public function get_instance() {
			
			if ( is_null( self::$instance ) ) {
				
				self::$instance = new self;
			}
			
			return self::$instance;
		}
	}
}