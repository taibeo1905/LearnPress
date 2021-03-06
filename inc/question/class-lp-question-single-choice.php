<?php

/**
 * LP_Question_Single_Choice
 *
 * @author  ThimPress
 * @package LearnPress/Templates
 * @version 1.0
 * @extends LP_Question
 */
class LP_Question_Single_Choice extends LP_Abstract_Question {
	/**
	 * Constructor
	 *
	 * @param mixed
	 * @param array
	 */
	function __construct( $the_question = null, $args = null ) {
		parent::__construct( $the_question, $args );

	}

	function admin_js_template(){
		ob_start();
		?>
		<tr class="lp-list-option lp-list-option-new lp-list-option-empty <# if(data.id){ #>lp-list-option-{{data.id}}<# } #>" data-id="{{data.id}}">
			<td>
				<input class="lp-answer-text no-submit key-nav" type="text" name="learn_press_question[{{data.question_id}}][answer][text][]" value="{{data.text}}" />
			</td>
			<th class="lp-answer-check">
				<input type="hidden" name="learn_press_question[{{data.question_id}}][answer][value][]" value="{{data.value}}" />
				<input type="radio" name="learn_press_question[{{data.question_id}}][checked][]" {{data.checked}} value="{{data.value}}" />
			</th>
			<td class="lp-list-option-actions lp-remove-list-option">
				<i class="dashicons dashicons-trash"></i>
			</td>
			<td class="lp-list-option-actions lp-move-list-option open-hand">
				<i class="dashicons dashicons-sort"></i>
			</td>
		</tr>
		<?php
		return apply_filters( 'learn_press_question_single_choice_answer_option_template', ob_get_clean(), __CLASS__ );
	}

	function submit_answer( $quiz_id, $answer ) {
		$questions = learn_press_get_question_answers( null, $quiz_id );
		if ( !is_array( $questions ) ) $questions = array();
		$questions[$quiz_id][$this->get( 'ID' )] = is_array( $answer ) ? reset( $answer ) : $answer;
		learn_press_save_question_answer( null, $quiz_id, $this->get( 'ID' ), is_array( $answer ) ? reset( $answer ) : $answer );
		//print_r($answer);
	}

	function get_icon(){
		return '<img src="' . apply_filters( 'learn_press_question_icon', LP()->plugin_url( 'assets/images/single-choice.png' ), $this ) . '">';
	}

	function admin_script() {
		parent::admin_script();
		return;
		?>
		<script type="text/html" id="tmpl-single-choice-question-answer">
			<tr class="lpr-disabled">
				<td class="lpr-sortable-handle">
					<i class="dashicons dashicons-sort"></i>
				</td>
				<td class="lpr-is-true-answer">
					<input type="hidden" name="lpr_question[{{data.question_id}}][answer][is_true][__INDEX__]" value="0" />
					<input type="radio" data-group="lpr-question-answer-{{data.question_id}}" name="lpr_question[{{data.question_id}}][answer][is_true][__INDEX__]" value="1" />

				</td>
				<td>
					<input class="lpr-answer-text" type="text" name="lpr_question[{{data.question_id}}][answer][text][__INDEX__]" value="" />
				</td>
				<td align="center" class="lpr-remove-answer">
					<span class=""><i class="dashicons dashicons-trash"></i></span></td>
			</tr>
		</script>
		<?php

	}

	/**
	 * @param bool $enqueue
	 */
	private function _admin_enqueue_script( $enqueue = true ) {
		ob_start();
		$key = 'question_' . $this->get( 'ID' );
		?>
		<script type="text/javascript">
			(function ($) {
				var $form = $('#post');
				$form.unbind('learn_press_question_before_update.<?php echo $key;?>').on('learn_press_question_before_update.<?php echo $key;?>', function () {
					var $question = $('.lpr-question-single-choice[data-id="<?php echo $this->get('ID');?>"]');
					if ($question.length) {
						var $input = $('.lpr-is-true-answer input[type="radio"]:checked', $question);
						if (0 == $input.length) {
							var message = $('.lpr-question-title input', $question).val();
							message += ": " + '<?php _e( 'No answer added to question or you must set an answer is correct!', 'learn_press' );?>'
							window.learn_press_before_update_quiz_message.push(message);
							return false;
						}
					}
				});
			})(jQuery);
		</script>
		<?php
		$script = ob_get_clean();
		if ( $enqueue ) {
			$script = preg_replace( '!</?script.*>!', '', $script );
			learn_press_enqueue_script( $script );
		} else {
			echo $script;
		}
	}

	function get_default_answers( $answers = false ) {
		if ( !$answers ) {
			$answers = array(
				array(
					'is_true' => 'yes',
					'value'   => 'option_first',
					'text'    => __( 'Option First', 'learn_press' )
				),
				array(
					'is_true' => 'no',
					'value'   => 'option_seconds',
					'text'    => __( 'Option Seconds', 'learn_press' )
				),
				array(
					'is_true' => 'no',
					'value'   => 'option_third',
					'text'    => __( 'Option Third', 'learn_press' )
				)
			);
		}
		return $answers;
	}

	function admin_interface( $args = array() ) {
		ob_start();
		$view = learn_press_get_admin_view( 'meta-boxes/question/single-choice-options.php' );
		include $view;
		$output = ob_get_clean();

		if ( !isset( $args['echo'] ) || ( isset( $args['echo'] ) && $args['echo'] === true ) ) {
			echo $output;
		}
		return $output;
	}

	function save_post_action() {
		if ( $post_id = $this->get( 'ID' ) ) {
			$post_data    = isset( $_POST[LP()->question_post_type] ) ? $_POST[LP()->question_post_type] : array();
			$post_answers = array();
			$post_explain = $post_data[$post_id]['explaination'];
			if ( isset( $post_data[$post_id] ) && $post_data = $post_data[$post_id] ) {
				wp_update_post(
					array(
						'ID'         => $post_id,
						'post_title' => $post_data['text'],
						'post_type'  => LP()->question_post_type
					)
				);
				$index = 0;
				foreach ( $post_data['answer']['text'] as $k => $txt ) {
					if ( !$txt ) continue;
					$post_answers[$index ++] = array(
						'text'    => $txt,
						'is_true' => $post_data['answer']['is_true'][$k]
					);
				}
			}
			$post_data['answer']       = $post_answers;
			$post_data['type']         = $this->get_type();
			$post_data['explaination'] = $post_explain;
			update_post_meta( $post_id, '_lpr_question', $post_data );
		}
		return $post_id;
	}

	function render( $args = null ) {
		$answered = ! empty( $args['answered'] ) ? $args['answered'] : array();
		$view = learn_press_locate_template( 'question/types/single-choice.php' );
		include $view;
	}

	function check( $user_answer = null ) {
		$return = array(
			'correct' => false,
			'mark'    => 0
		);
		if ( $answers = $this->answers ) {
			foreach ( $answers as $k => $answer ) {
				if( ( $answer['is_true'] == 'yes' ) && ( $answer['value'] == $user_answer ) ){
					$return['correct'] = true;
					$return['mark'] = floatval( $this->mark );
					break;
				}
			}
		}
		return $return;
	}
}
///