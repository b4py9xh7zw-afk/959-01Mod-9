<?php
$pageTitle = '升级历史记录 - 许可证管理平台';
require_once __DIR__ . '/../layouts/header.php';
?>

<div class="max-w-5xl mx-auto space-y-8">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
            升级历史记录
        </h1>
        <a href="/licenses/view?id=<?php echo $license['id']; ?>" class="px-6 py-3 bg-gray-200 text-gray-700 rounded-lg font-semibold hover:bg-gray-300 transition-colors">
            ← 返回许可证详情
        </a>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">许可证信息</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="text-sm text-gray-500 mb-1">许可证密钥</div>
                <code class="text-sm font-mono text-gray-800"><?php echo htmlspecialchars($license['license_key']); ?></code>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="text-sm text-gray-500 mb-1">产品名称</div>
                <div class="text-lg font-medium text-gray-800"><?php echo htmlspecialchars($license['product_name']); ?></div>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="text-sm text-gray-500 mb-1">当前状态</div>
                <span class="px-3 py-1 inline-flex text-sm leading-5 font-semibold rounded-full <?php 
                    echo $license['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                        ($license['status'] === 'expired' ? 'bg-red-100 text-red-800' : 
                        ($license['status'] === 'upgraded' ? 'bg-blue-100 text-blue-800' :
                        ($license['status'] === 'downgraded' ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-800'))); 
                ?>">
                    <?php 
                    echo $license['status'] === 'active' ? '活跃' : 
                        ($license['status'] === 'expired' ? '已过期' : 
                        ($license['status'] === 'upgraded' ? '已升级' :
                        ($license['status'] === 'downgraded' ? '已回退' : '未激活'))); 
                    ?>
                </span>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg border border-gray-100 p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-6">历史变更记录</h2>
        
        <?php if (empty($history)): ?>
            <div class="text-center py-12 text-gray-500">
                <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-lg">暂无升级或回退记录</p>
            </div>
        <?php else: ?>
            <div class="relative">
                <div class="absolute left-6 top-0 bottom-0 w-0.5 bg-gradient-to-b from-blue-400 via-purple-400 to-pink-400"></div>
                
                <div class="space-y-8">
                    <?php foreach ($history as $record): ?>
                        <div class="relative pl-16">
                            <div class="absolute left-4 w-5 h-5 rounded-full border-4 border-white shadow-md <?php 
                                echo $record['type'] === 'upgrade' ? 'bg-blue-500' : 'bg-orange-500'; 
                            ?>"></div>
                            
                            <div class="bg-gray-50 rounded-xl p-6 border border-gray-100">
                                <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                                    <div class="flex items-center space-x-3">
                                        <span class="px-3 py-1 text-xs font-semibold rounded-full <?php 
                                            echo $record['type'] === 'upgrade' ? 'bg-blue-100 text-blue-800' : 'bg-orange-100 text-orange-800'; 
                                        ?>">
                                            <?php echo $record['type'] === 'upgrade' ? '↑ 升级' : '↓ 回退'; ?>
                                        </span>
                                        <span class="text-sm text-gray-500">
                                            <?php echo date('Y-m-d H:i:s', strtotime($record['created_at'])); ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($record['order_no'])): ?>
                                        <span class="text-sm text-gray-600">
                                            订单号: <code class="bg-white px-2 py-1 rounded text-xs"><?php echo htmlspecialchars($record['order_no']); ?></code>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                                        <div class="text-xs font-medium text-gray-500 mb-2">变更前</div>
                                        <div class="space-y-1">
                                            <div class="flex items-center">
                                                <span class="text-xs text-gray-500 mr-2">许可证:</span>
                                                <code class="text-xs font-mono bg-gray-100 px-2 py-0.5 rounded"><?php echo htmlspecialchars($record['old_license_key']); ?></code>
                                            </div>
                                            <div class="flex items-center">
                                                <span class="text-xs text-gray-500 mr-2">产品:</span>
                                                <span class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($record['old_product_name']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-white p-4 rounded-lg border border-gray-200">
                                        <div class="text-xs font-medium text-gray-500 mb-2">变更后</div>
                                        <div class="space-y-1">
                                            <div class="flex items-center">
                                                <span class="text-xs text-gray-500 mr-2">许可证:</span>
                                                <code class="text-xs font-mono bg-gray-100 px-2 py-0.5 rounded"><?php echo htmlspecialchars($record['new_license_key']); ?></code>
                                            </div>
                                            <div class="flex items-center">
                                                <span class="text-xs text-gray-500 mr-2">产品:</span>
                                                <span class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($record['new_product_name']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                                    <div class="bg-white p-3 rounded-lg border border-gray-200">
                                        <div class="text-xs text-gray-500">剩余天数</div>
                                        <div class="text-lg font-bold text-gray-800"><?php echo $record['remaining_days']; ?> 天</div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border border-gray-200">
                                        <div class="text-xs text-gray-500">价格差额</div>
                                        <div class="text-lg font-bold <?php echo $record['price_difference'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">
                                            <?php echo $record['price_difference'] >= 0 ? '+' : ''; ?>¥<?php echo number_format($record['price_difference'], 2); ?>
                                        </div>
                                    </div>
                                    <div class="bg-white p-3 rounded-lg border border-gray-200">
                                        <div class="text-xs text-gray-500">旧授权处置</div>
                                        <div class="text-sm font-medium text-gray-800">
                                            <?php 
                                            $disposalMap = ['retain' => '保留', 'cancel' => '取消', 'convert' => '转换'];
                                            echo $disposalMap[$record['old_license_disposal']] ?? $record['old_license_disposal'];
                                            ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php 
                                $pluginsAdded = !empty($record['plugins_added']) ? json_decode($record['plugins_added'], true) : [];
                                $pluginsRemoved = !empty($record['plugins_removed']) ? json_decode($record['plugins_removed'], true) : [];
                                ?>
                                <?php if (!empty($pluginsAdded) || !empty($pluginsRemoved)): ?>
                                    <div class="mt-4 pt-4 border-t border-gray-200">
                                        <div class="text-xs font-medium text-gray-500 mb-2">插件变更</div>
                                        <div class="flex flex-wrap gap-2">
                                            <?php if (!empty($pluginsAdded)): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M12 7a1 1 0 011 1v2h2a1 1 0 110 2h-2v2a1 1 0 11-2 0v-2H9a1 1 0 110-2h2V8a1 1 0 011-1z" clip-rule="evenodd"/>
                                                    </svg>
                                                    新增: <?php echo implode(', ', array_column($pluginsAdded, 'name')); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if (!empty($pluginsRemoved)): ?>
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M5 10a1 1 0 011-1h8a1 1 0 110 2H6a1 1 0 01-1-1z" clip-rule="evenodd"/>
                                                    </svg>
                                                    移除: <?php echo implode(', ', array_column($pluginsRemoved, 'name')); ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if (!empty($record['reason'])): ?>
                                    <div class="mt-4 pt-4 border-t border-gray-200">
                                        <div class="text-xs font-medium text-gray-500 mb-1">变更原因</div>
                                        <p class="text-sm text-gray-700"><?php echo htmlspecialchars($record['reason']); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($upgradeChain)): ?>
        <div class="bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl border border-blue-200 p-6">
            <h3 class="text-lg font-semibold text-blue-800 mb-4">完整升级链路</h3>
            <div class="flex flex-wrap items-center gap-2">
                <?php foreach ($upgradeChain as $idx => $link): ?>
                    <div class="bg-white px-4 py-2 rounded-lg border border-gray-200 shadow-sm">
                        <div class="text-xs text-gray-500"><?php echo $link['type'] === 'upgrade' ? '升级' : '回退'; ?></div>
                        <div class="text-xs font-mono text-gray-800">
                            <?php echo htmlspecialchars($link['old_license_key']); ?>
                        </div>
                    </div>
                    <?php if ($idx < count($upgradeChain) - 1): ?>
                        <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../layouts/footer.php'; ?>
